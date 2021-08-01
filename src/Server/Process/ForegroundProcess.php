<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\{
    Server\Process as ProcessInterface,
    Exception\ProcessTimedOut,
};
use Innmind\Immutable\{
    Sequence,
    Str,
    Maybe,
    Either,
};
use Symfony\Component\Process\{
    Process,
    Exception\ProcessTimedOutException,
};

final class ForegroundProcess implements ProcessInterface
{
    private Process $process;
    private Output $output;
    private ?ExitCode $exitCode = null;

    public function __construct(Process $process, bool $streamOutput = false)
    {
        $generator = static function() use ($process): \Generator {
            /**
             * @var string $key
             * @var string $value
             */
            foreach ($process->getIterator() as $key => $value) {
                $type = $key === Process::OUT ? Output\Type::output() : Output\Type::error();

                /** @psalm-suppress RedundantCastGivenDocblockType Don't trust the symfony process */
                yield [Str::of((string) $value), $type];
            }

            // we wait the process to finish after iterating over the output in
            // order to correctly close the pipes to the process when the user
            // only iterates over the output so he doesn't have to call the wait
            // function automatically. It also fix a bug where too many pipes are
            // opened (consequently preventing from running new processes) when
            // we run too many processes but never wait them to finish.
            $process->wait();
        };

        $this->process = $process;

        if ($streamOutput) {
            /** @var Sequence<array{0: Str, 1: Output\Type}> */
            $output = Sequence::lazy($generator);
        } else {
            /** @var Sequence<array{0: Str, 1: Output\Type}> */
            $output = Sequence::defer(($generator)());
        }

        $this->output = new Output\Output($output);
    }

    public function pid(): Maybe
    {
        return Maybe::of($this->process->getPid())->map(static fn($pid) => new Pid($pid));
    }

    public function output(): Output
    {
        return $this->output;
    }

    public function wait(): Either
    {
        try {
            $this->process->wait();

            return Either::right(
                Maybe::of($this->process->getExitCode())
                    ->map(static fn($code) => new ExitCode($code)),
            );
        } catch (ProcessTimedOutException $e) {
            return Either::left(new ProcessTimedOut(
                $e->getMessage(),
                (int) $e->getCode(),
                $e,
            ));
        }
    }

    public function isRunning(): bool
    {
        return $this->process->isRunning();
    }
}
