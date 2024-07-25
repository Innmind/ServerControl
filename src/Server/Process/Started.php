<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\{
    Server\Process\Output\Chunk,
    Server\Process\Output\Type,
    Server\Second,
    Server\Signal,
    Exception\RuntimeException,
};
use Innmind\Filesystem\File\Content;
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
    Stream,
    Watch,
    Capabilities,
};
use Innmind\Immutable\{
    Maybe,
    Str,
    Sequence,
    Either,
    SideEffect,
    Set,
    Predicate\Instance,
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
final class Started
{
    private Clock $clock;
    private Watch $watch;
    private Halt $halt;
    private Period $grace;
    private bool $background;
    private PointInTime $startedAt;
    /** @var resource */
    private $process;
    private Readable\NonBlocking $output;
    private Readable\NonBlocking $error;
    private Writable $input;
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
        Halt $halt,
        Capabilities $capabilities,
        Period $grace,
        callable $start,
        bool $background,
        Maybe $timeout,
        Maybe $content,
    ) {
        $this->clock = $clock;
        $this->halt = $halt;
        $this->grace = $grace;
        $this->background = $background;
        $this->startedAt = $clock->now();
        // we defer the start of the process here instead of starting it in Unix
        // to better control the property startedAt in case it needs to be moved
        // after the process is really started
        [$this->process, $pipes] = $start();
        $this->output = Readable\NonBlocking::of(
            $capabilities->readable()->acquire($pipes[1]),
        );
        $this->error = Readable\NonBlocking::of(
            $capabilities->readable()->acquire($pipes[2]),
        );
        $this->input = $capabilities->writable()->acquire($pipes[0]);
        // We use a short timeout to watch the streams when there is a timeout
        // defined on the command to make sure we're as close as possible to the
        // defined value without using polling.
        // When simply reading the output we can't wait forever as the tests
        // hang forever on Linux.
        $this->watch = $capabilities
            ->watch()
            ->timeoutAfter(ElapsedPeriod::of(100))
            ->forRead(
                $this->output,
                $this->error,
            )
            ->forWrite($this->input);
        $this->timeout = $timeout;
        $this->content = $content;
        $this->pid = new Pid($this->status()['pid']);
    }

    public function pid(): Pid
    {
        return $this->pid;
    }

    /**
     * @return Either<ExitCode|'signaled'|'timed-out', SideEffect>
     */
    public function wait(): Either
    {
        // we don't need to keep the output read while writing to the input
        // stream as this data will never be exposed to caller, so by discarding
        // this data we prevent ourself from reaching a possible "out of memory"
        // error
        /** @var Either<ExitCode|'signaled'|'timed-out', SideEffect> */
        return $this
            ->output()
            ->last()
            ->keep(Instance::of(Either::class))
            ->match(
                static fn($return) => $return,
                static fn() => throw new RuntimeException('Unable to retrieve process result'),
            );
    }

    /**
     * @return Sequence<Chunk|Either<ExitCode|'signaled'|'timed-out', SideEffect>>
     */
    public function output(): Sequence
    {
        $this->ensureExecuteOnce();

        return Sequence::lazy(function() {
            yield $this->writeInputAndRead();

            $this->watch = $this->watch->unwatch($this->input);

            do {
                yield $this->readOnce();

                $timedOut = $this->checkTimeout()->match(
                    static fn() => true,
                    static fn() => false,
                );

                if ($timedOut) {
                    /** @var Sequence<Either<ExitCode|'signaled'|'timed-out', SideEffect>> */
                    yield Sequence::of(Either::left($this->abort()));

                    return;
                }

                $status = $this->status();
            } while ($status['running']);

            // we don't read the remaining data in the streams for background
            // processes because it will hang until the concrete process is really
            // finished, thus defeating the purpose of launching the process in the
            // background
            while (!$this->background && $this->outputStillOpen()) {
                // even though the process is no longer running there might stil be
                // data to be read in the streams
                $chunks = $this->readOnce();

                yield $chunks;

                if ($chunks->empty()) {
                    // do not try to continue reading the streams when no output
                    // otherwise for commands like "tail -f" it will run forever
                    break;
                }

                // no need to check for timeouts here since the process is no longer
                // running
            }

            $this->close();

            if ($status['signaled'] || $status['stopped']) {
                /** @var Sequence<Either<ExitCode|'signaled'|'timed-out', SideEffect>> */
                yield Sequence::of(Either::left('signaled'));

                return;
            }

            $exitCode = new ExitCode($status['exitcode']);

            if (!$exitCode->successful()) {
                /** @var Sequence<Either<ExitCode|'signaled'|'timed-out', SideEffect>> */
                yield Sequence::of(Either::left($exitCode));

                return;
            }

            /** @var Sequence<Either<ExitCode|'signaled'|'timed-out', SideEffect>> */
            yield Sequence::of(Either::right(new SideEffect));
        })->flatMap(static fn($chunks) => $chunks);
    }

    /**
     * @return Status
     */
    private function status(): array
    {
        /** @var Status */
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
     * @return Sequence<Chunk>
     */
    private function writeInputAndRead(): Sequence
    {
        return $this
            ->content
            ->map(static fn($content) => $content->chunks())
            ->otherwise(function() {
                $this->closeInput();

                /** @var Maybe<Sequence<Str>> */
                return Maybe::nothing();
            })
            ->match(
                fn($chunks) => $this->writeAndRead(
                    $this->input,
                    $chunks,
                ),
                static fn() => Sequence::of(),
            );
    }

    /**
     * @param Sequence<Str> $chunks
     *
     * @return Sequence<Chunk>
     */
    private function writeAndRead(
        Writable $stream,
        Sequence $chunks,
    ): Sequence {
        return Sequence::lazy(function() use ($chunks, $stream) {
            yield $chunks
                ->map(static fn($chunk) => $chunk->toEncoding(Str\Encoding::ascii))
                ->flatMap(function($chunk) use ($stream) {
                    // leave the exception here in case we can't write to the
                    // input stream because for now there is no clear way to
                    // handle this case
                    // todo if the DataPartiallyWritten case happen in concrete
                    // apps when the case should be handled by trying to rewrite
                    // the part of the chunk that hasn't be written. This is not
                    // done at this moment for sake of simplicity while the case
                    // has never been encountered
                    $_ = $this
                        ->waitAvailable($stream)
                        ->write($chunk)
                        ->match(
                            static fn($stream) => $stream,
                            static fn($e) => throw new RuntimeException($e::class),
                        );

                    return $this->readOnce();
                });
            $this->closeInput();
        })
            ->flatMap(static fn($chunks) => $chunks);
    }

    private function waitAvailable(Writable $stream): Writable
    {
        do {
            /** @var Set<Writable> */
            $toWrite = ($this->watch)()->match(
                static fn($ready) => $ready->toWrite(),
                static fn() => Set::of(),
            );
        } while (!$toWrite->contains($stream));

        return $stream;
    }

    private function closeInput(): void
    {
        // we crash the app if we fail to close the input stream be cause the
        // underlying process receiving the input may not behave correctly, in
        // some cases this could result on this process hanging forever
        // there is no way to recover safely from unpredictable behaviour so it's
        // better to stop everything
        $_ = $this->input->close()->match(
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

    private function maybeUnwatch(Watch $watch, Stream $stream): Watch
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
     * @return Sequence<Chunk>
     */
    private function readOnce(): Sequence
    {
        /** @var Set<Readable> */
        $toRead = ($this->watch)()->match(
            static fn($ready) => $ready->toRead(),
            static fn() => Set::of(),
        );

        $chunks = $toRead
            ->map(fn($stream) => match ($stream) {
                $this->output => Chunk::of($this->read($stream), Type::output),
                $this->error => Chunk::of($this->read($stream), Type::error),
            })
            ->filter(static fn($chunk) => !$chunk->data()->empty())
            ->toList();

        $this->watch = $toRead->reduce(
            $this->watch,
            $this->maybeUnwatch(...),
        );

        return Sequence::of(...$chunks);
    }

    /**
     * @return Maybe<'timed-out'>
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
            ->map(static fn() => 'timed-out');
    }

    /**
     * @return 'timed-out'
     */
    private function abort(): string
    {
        @\proc_terminate($this->process);
        ($this->halt)($this->grace);

        if ($this->status()['running']) {
            @\proc_terminate($this->process, Signal::kill->toInt());
        }

        $this->close();

        return 'timed-out';
    }

    private function outputStillOpen(): bool
    {
        if (!$this->output->end() && !$this->output->closed()) {
            return true;
        }

        if (!$this->error->end() && !$this->error->closed()) {
            return true;
        }

        return false;
    }
}
