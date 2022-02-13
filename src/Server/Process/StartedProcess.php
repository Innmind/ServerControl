<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Server\Process\Output\Type;
use Innmind\Filesystem\{
    File\Content,
    Adapter\Chunk,
};
use Innmind\TimeContinuum\Earth\ElapsedPeriod;
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
};

/**
 * @internal
 */
final class StartedProcess
{
    /** @var resource */
    private $process;
    private Readable\NonBlocking $output;
    private Readable\NonBlocking $error;
    private Writable $input;
    /** @var Maybe<Content> */
    private Maybe $content;
    private Pid $pid;
    private bool $executed = false;

    /**
     * @param resource $process
     * @param array{0: resource, 1: resource, 2: resource} $pipes
     * @param Maybe<Content> $content
     */
    public function __construct($process, array $pipes, Maybe $content)
    {
        $this->process = $process;
        $this->output = Readable\NonBlocking::of(
            Readable\Stream::of($pipes[1]),
        );
        $this->error = Readable\NonBlocking::of(
            Readable\Stream::of($pipes[2]),
        );
        $this->input = Writable\Stream::of($pipes[0]);
        $this->content = $content;
        $this->pid = new Pid($this->status()['pid']);
    }

    public function pid(): Pid
    {
        return $this->pid;
    }

    public function wait(): ExitCode
    {
        $output = $this->output();

        foreach ($output as $_ => $__) {
            // do nothing with the output
        }

        $status = $output->getReturn();

        // todo handle signaled/stopped
        return new ExitCode($status['exitcode']);
    }

    /**
     * @return \Generator<Type, Str>
     */
    public function output(): \Generator
    {
        $this->ensureExecuteOnce();

        $this->writeInput();

        $select = Watch\Select::timeoutAfter(new ElapsedPeriod(0))->forRead(
            $this->output,
            $this->error,
        );

        do {
            $toRead = $select()->match(
                static fn($ready) => $ready->toRead(),
                static fn() => throw new \RuntimeException('Failed to read process output'),
            );

            $chunks = $toRead
                ->map(fn($stream) => match ($stream) {
                    $this->output => [$this->read($stream), Type::output],
                    $this->error => [$this->read($stream), Type::error],
                })
                ->toList();

            foreach ($chunks as [$chunk, $type]) {
                yield $type => $chunk;
            }

            $select = $this->maybeUnwatch($select, $this->output);
            $select = $this->maybeUnwatch($select, $this->error);

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

    private function maybeUnwatch(Watch $select, Readable\NonBlocking $stream): Watch
    {
        if ($stream->end() || $stream->closed()) {
            $select = $select->unwatch($stream);
        }

        return $select;
    }

    private function read(Readable $stream): Str
    {
        return $stream->read()->match(
            static fn($chunk) => $chunk,
            static fn() => Str::of(''),
        );
    }
}
