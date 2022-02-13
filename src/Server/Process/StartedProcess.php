<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\{
    Server\Process\Output\Type,
    Server\Second,
    Server\Signal,
    ProcessFailed,
    ProcessSignaled,
    ProcessTimedOut,
    Exception\RuntimeException,
};
use Innmind\Filesystem\{
    File\Content,
    Adapter\Chunk,
};
use Innmind\TimeContinuum\{
    Clock,
    PointInTime,
    Period,
    Earth\ElapsedPeriod,
};
use Innmind\TimeWarp\Halt;
use Innmind\Stream\{
    Readable,
    Writable,
    Watch,
    Selectable,
};
use Innmind\Immutable\{
    Maybe,
    Str,
    Sequence,
    Either,
    SideEffect,
    Set,
};

/**
 * @internal
 * @psalm-type Status = array{
 *         pid: int<2, max>,
 *         running: bool,
 *         stopped: bool,
 *         signaled: bool,
 *         exitcode: int<0, 255>,
 * }
 */
final class StartedProcess
{
    private Clock $clock;
    private Watch $watch;
    private Halt $halt;
    private Period $grace;
    private PointInTime $startedAt;
    /** @var resource */
    private $process;
    private Readable\NonBlocking $output;
    private Readable\NonBlocking $error;
    private Writable\Stream $input;
    /** @var Maybe<Second> */
    private Maybe $timeout;
    /** @var Maybe<Content> */
    private Maybe $content;
    private Pid $pid;
    private bool $executed = false;

    /**
     * @param callable(): array{0: resource, 1: array{0: resource, 1: resource, 2: resource}} $start
     * @param Maybe<Second> $timeout
     * @param Maybe<Content> $content
     */
    public function __construct(
        Clock $clock,
        Watch $watch,
        Halt $halt,
        Period $grace,
        callable $start,
        Maybe $timeout,
        Maybe $content,
    ) {
        $this->clock = $clock;
        $this->watch = $watch;
        $this->halt = $halt;
        $this->grace = $grace;
        $this->startedAt = $clock->now();
        // we defer the start of the process here instead of starting it in Unix
        // to better control the property startedAt in case it needs to be moved
        // after the process is really started
        [$this->process, $pipes] = $start();
        $this->output = Readable\NonBlocking::of(
            Readable\Stream::of($pipes[1]),
        );
        $this->error = Readable\NonBlocking::of(
            Readable\Stream::of($pipes[2]),
        );
        $this->input = Writable\Stream::of($pipes[0]);
        $this->timeout = $timeout;
        $this->content = $content;
        $this->pid = new Pid($this->status()['pid']);
    }

    public function pid(): Pid
    {
        return $this->pid;
    }

    /**
     * @return Either<ProcessFailed|ProcessSignaled|ProcessTimedOut, SideEffect>
     */
    public function wait(): Either
    {
        $output = $this->output();

        foreach ($output as $_) {
            // do nothing with the output
        }

        $status = $output->getReturn();

        if ($status instanceof ProcessTimedOut) {
            /** @var Either<ProcessFailed|ProcessSignaled|ProcessTimedOut, SideEffect> */
            return Either::left($status);
        }

        if ($status['signaled'] || $status['stopped']) {
            /** @var Either<ProcessFailed|ProcessSignaled|ProcessTimedOut, SideEffect> */
            return Either::left(new ProcessSignaled);
        }

        $exitCode = new ExitCode($status['exitcode']);

        if (!$exitCode->successful()) {
            /** @var Either<ProcessFailed|ProcessSignaled|ProcessTimedOut, SideEffect> */
            return Either::left(new ProcessFailed($exitCode));
        }

        /** @var Either<ProcessFailed|ProcessSignaled|ProcessTimedOut, SideEffect> */
        return Either::right(new SideEffect);
    }

    /**
     * @return \Generator<int, array{0: Str, 1: Type}, mixed, Status|ProcessTimedOut>
     */
    public function output(): \Generator
    {
        $this->ensureExecuteOnce();

        $watch = $this->watch->forRead(
            $this->output,
            $this->error,
        );

        [$watch, $chunks] = $this->writeInputAndRead($watch);

        foreach ($chunks->toList() as $value) {
            yield $value;
        }

        do {
            [$watch, $chunks] = $this->readOnce($watch);

            foreach ($chunks->toList() as $value) {
                yield $value;
            }

            $timedOut = $this->checkTimeout()->match(
                static fn() => true,
                static fn() => false,
            );

            if ($timedOut) {
                return $this->abort();
            }

            $status = $this->status();
        } while ($status['running']);

        $this->close();

        return $status;
    }

    /**
     * @return Status
     */
    private function status(): array
    {
        return \proc_get_status($this->process);
    }

    /**
     * We are forced to read the process output while we are writing to its input
     * otherwise the whole thing may hang because if the process outputed a lot
     * of data then the output pipe is too big and it prevents us from writing
     * to the input stream. This can be a problem in some cases where there is a
     * large amount of data written to the input and a lot of data read during
     * this process because the output will be kept in memory before being able
     * to send it back to the caller. This may result in an "out of memory" error
     *
     * @return array{0: Watch, 1: Sequence<array{0: Str, 1: Type}>}
     */
    private function writeInputAndRead(Watch $watch): array
    {
        return $this
            ->content
            ->map(static fn($content) => (new Chunk)($content))
            ->otherwise(function() {
                $this->closeInput($this->input);

                /** @var Maybe<Sequence<Str>> */
                return Maybe::nothing();
            })
            ->match(
                fn($chunks) => $this->writeAndRead(
                    $watch,
                    $this->input,
                    $chunks,
                    Sequence::of(),
                ),
                static fn() => [$watch, Sequence::of()],
            );
    }

    /**
     * @param Sequence<Str> $chunks
     * @param Sequence<array{0: Str, 1: Type}> $output
     *
     * @return array{0: Watch, 1: Sequence<array{0: Str, 1: Type}>}
     */
    private function writeAndRead(
        Watch $watch,
        Writable\Stream $stream,
        Sequence $chunks,
        Sequence $output,
    ): array {
        [$watch, $output, $stream] = $chunks
            ->map(static fn($chunk) => $chunk->toEncoding('ASCII'))
            ->reduce(
                [$watch, $output, $stream],
                function($state, $chunk) {
                    /**
                     * @var Watch $watch
                     * @var Sequence<array{0: Str, 1: Type}> $output
                     * @var Writable\Stream $stream
                     * @psalm-suppress MixedAssignment
                     * @psalm-suppress MixedArrayAccess
                     */
                    [$watch, $output, $stream] = $state;
                    // leave the exception here in case we can't write to the
                    // input stream because for now there is no clear way to
                    // handle this case
                    // todo if the DataPartiallyWritten case happen in concrete
                    // apps when the case should be handled by trying to rewrite
                    // the part of the chunk that hasn't be written. This is not
                    // done at this moment for sake of simplicity while the case
                    // has never been encountered
                    $stream = $this
                        ->waitAvailable($stream)
                        ->write($chunk)
                        ->match(
                            static fn($stream) => $stream,
                            static fn($e) => throw new RuntimeException($e::class),
                        );
                    [$watch, $read] = $this->readOnce($watch);

                    return [$watch, $output->append($read), $stream];
                },
            );
        $this->closeInput($stream);

        return [$watch, $output];
    }

    private function waitAvailable(Writable\Stream $stream): Writable
    {
        $watch = $this->watch->forWrite($stream);

        do {
            /** @var Set<Selectable> */
            $toWrite = $watch()->match(
                static fn($ready) => $ready->toWrite(),
                static fn() => Set::of(),
            );
        } while (!$toWrite->contains($stream));

        return $stream;
    }

    private function closeInput(Writable $input): void
    {
        // we crash the app if we fail to close the input stream be cause the
        // underlying process receiving the input may not behave correctly, in
        // some cases this could result on this process hanging forever
        // there is no way to recover safely from unpredictable behaviour so it's
        // better to stop everything
        $_ = $input->close()->match(
            static fn() => null, // closed correctly
            static fn() => throw new RuntimeException('Failed to close input stream'),
        );
    }

    private function close(): void
    {
        // this will automatically close all the pipes (input, output and error)
        \proc_close($this->process);
    }

    private function ensureExecuteOnce(): void
    {
        if ($this->executed) {
            throw new \LogicException('Cannot call both wait() and output() on the same process, or call them twice');
        }

        $this->executed = true;
    }

    private function maybeUnwatch(Watch $watch, Selectable $stream): Watch
    {
        if ($stream->end() || $stream->closed()) {
            $watch = $watch->unwatch($stream);
        }

        return $watch;
    }

    private function read(Readable $stream): Str
    {
        return $stream->read()->match(
            static fn($chunk) => $chunk,
            static fn() => Str::of(''),
        );
    }

    /**
     * @return array{0: Watch, 1: Sequence<array{0: Str, 1: Type}>}
     */
    private function readOnce(Watch $watch): array
    {
        /** @var Set<Selectable&Readable> */
        $toRead = $watch()->match(
            static fn($ready) => $ready->toRead(),
            static fn() => Set::of(),
        );

        /** @var list<array{0: Str, 1: Type}> */
        $chunks = $toRead
            ->map(fn($stream) => match ($stream) {
                $this->output => [$this->read($stream), Type::output],
                $this->error => [$this->read($stream), Type::error],
            })
            ->filter(static fn($pair) => !$pair[0]->empty())
            ->toList();

        $watch = $toRead->reduce(
            $watch,
            $this->maybeUnwatch(...),
        );

        return [$watch, Sequence::of(...$chunks)];
    }

    /**
     * @return Maybe<ProcessTimedOut>
     */
    private function checkTimeout(): Maybe
    {
        return $this
            ->timeout
            ->map(static fn($second) => new ElapsedPeriod(
                $second->toInt() * 1000,
            ))
            ->filter(
                fn($threshold) => $this
                    ->clock
                    ->now()
                    ->elapsedSince($this->startedAt)
                    ->longerThan($threshold),
            )
            ->map(static fn() => new ProcessTimedOut);
    }

    private function abort(): ProcessTimedOut
    {
        @\proc_terminate($this->process);
        ($this->halt)($this->grace);

        if ($this->status()['running']) {
            @\proc_terminate($this->process, Signal::kill->toInt());
        }

        $this->close();

        return new ProcessTimedOut;
    }
}
