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
    DataPartiallyWritten,
};
use Innmind\Immutable\{
    Maybe,
    Str,
    Sequence,
    Either,
    SideEffect,
};

/**
 * @internal
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

        foreach ($output as $_ => $__) {
            // do nothing with the output
        }

        $status = $output->getReturn();

        if ($status instanceof ProcessTimedOut) {
            return Either::left($status);
        }

        if ($status['signaled'] || $status['signaled']) {
            return Either::left(new ProcessSignaled);
        }

        $exitCode = new ExitCode($status['exitcode']);

        if (!$exitCode->successful()) {
            return Either::left(new ProcessFailed($exitCode));
        }

        return Either::right(new SideEffect);
    }

    /**
     * @return \Generator<Type, Str>
     */
    public function output(): \Generator
    {
        $this->ensureExecuteOnce();

        $this->writeInput();

        $watch = $this->watch->forRead(
            $this->output,
            $this->error,
        );

        do {
            [$watch, $chunks] = $this->readOnce($watch);

            foreach ($chunks as [$chunk, $type]) {
                yield $type => $chunk;
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
     * @return array{
     *         pid: int<2, max>,
     *         running: bool,
     *         stopped: bool,
     *         signaled: bool,
     *         exitcode: int<0, 255>,
     * }
     */
    private function status(): array
    {
        return \proc_get_status($this->process);
    }

    private function writeInput(): void
    {

    }

    private function close(): void
    {
        // this will automatically close all the pipes (input, output and error)
        \proc_close($this->process);
    }

    private function ensureExecuteOnce(): void
    {
        if ($this->executed) {
            throw new \RuntimeException('Cannot call both wait() and output() on the same process');
        }

        $this->executed = true;
    }

    private function maybeUnwatch(Watch $watch, Readable $stream): Watch
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
     * @return [Watch, Set<array{0: Str, 1: Type}>]
     */
    private function readOnce(Watch $watch): array
    {
        $toRead = $watch()->match(
            static fn($ready) => $ready->toRead(),
            static fn() => throw new \RuntimeException('Failed to read process output'),
        );

        $chunks = $toRead
            ->map(fn($stream) => match ($stream) {
                $this->output => [$this->read($stream), Type::output],
                $this->error => [$this->read($stream), Type::error],
            })
            ->toList();

        $watch = $toRead->reduce(
            $watch,
            $this->maybeUnwatch(...),
        );

        return [$watch, $chunks];
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
