<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\{
    Server\Process,
    Server\Process\Output\Chunk,
    Exception\RuntimeException,
};
use Innmind\Immutable\{
    Sequence,
    Maybe,
    Either,
    Predicate\Instance,
};

/**
 * @internal
 */
final class Foreground
{
    private Started $process;
    /** @var Sequence<Chunk> */
    private Sequence $output;
    /** @var ?Either<Failed|Signaled|TimedOut, Success> */
    private ?Either $status = null;

    public function __construct(Started $process, bool $streamOutput)
    {
        $this->process = $process;
        $yieldOutput = function() use ($process): \Generator {
            yield $process
                ->output()
                ->map(function($chunk) {
                    if ($chunk instanceof Either) {
                        $this->status = $chunk
                            ->map(fn() => new Success($this->output))
                            ->leftMap(fn($error) => match ($error) {
                                'timed-out' => new TimedOut($this->output),
                                'signaled' => new Signaled($this->output),
                                default => new Failed($error, $this->output),
                            });
                    }

                    return $chunk;
                })
                ->keep(Instance::of(Chunk::class));
        };

        if ($streamOutput) {
            $output = Sequence::lazy($yieldOutput)->flatMap(
                static fn($chunks) => $chunks,
            );
        } else {
            $output = Sequence::defer($yieldOutput())->flatMap(
                static fn($chunks) => $chunks,
            );
        }

        $this->output = $output;
    }

    /**
     * @return Maybe<Pid>
     */
    public function pid(): Maybe
    {
        return Maybe::of($this->process->pid());
    }

    /**
     * @return Sequence<Chunk>
     */
    public function output(): Sequence
    {
        return $this->output;
    }

    /**
     * @return Either<TimedOut|Failed|Signaled, Success>
     */
    public function wait(): Either
    {
        if (\is_null($this->status)) {
            // we iterate over the output here to keep it in memory (when not
            // streamed) so it is available when calling output() after wait()
            // the exit status will be set on this process when done iterating
            // over the output (@see __construct)
            $_ = $this->output->foreach(static fn() => null);
        }

        if (\is_null($this->status)) {
            throw new RuntimeException('Unable to retrieve the status');
        }

        // the status should always be set here because we iterated over the
        // output above
        return $this->status;
    }
}
