<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\{
    Server\Process,
    Exception\RuntimeException,
};
use Innmind\Immutable\{
    Sequence,
    Maybe,
    Either,
};

final class Foreground implements Process
{
    private Started $process;
    private Output $output;
    /** @var ?Either<Failed|Signaled|TimedOut, Success> */
    private ?Either $status = null;

    public function __construct(Started $process, bool $streamOutput = false)
    {
        $this->process = $process;
        $yieldOutput = function() use ($process): \Generator {
            $output = $process->output();

            foreach ($output as $chunk) {
                yield $chunk;
            }

            $this->status = $output
                ->getReturn()
                ->map(fn() => new Success($this->output))
                ->leftMap(fn($error) => match ($error) {
                    'timed-out' => new TimedOut($this->output),
                    'signaled' => new Signaled($this->output),
                    default => new Failed($error, $this->output),
                });
        };

        if ($streamOutput) {
            $output = Sequence::lazy($yieldOutput);
        } else {
            $output = Sequence::defer($yieldOutput());
        }

        $this->output = new Output\Output($output);
    }

    public function pid(): Maybe
    {
        return Maybe::of($this->process->pid());
    }

    public function output(): Output
    {
        return $this->output;
    }

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
