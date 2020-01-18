<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\{
    Server\Process as ProcessInterface,
    Exception\ProcessStillRunning,
};
use Innmind\Immutable\{
    Sequence,
    Str,
};
use Symfony\Component\Process\Process;

final class ForegroundProcess implements ProcessInterface
{
    private Process $process;
    private ?Pid $pid;
    private ?Output $output;
    private ?ExitCode $exitCode;

    public function __construct(Process $process)
    {
        $this->process = $process;
        $this->output = new Output\Output(Sequence::defer(
            'array',
            (function(Process $process) {
                foreach ($process->getIterator() as $key => $value) {
                    $type = $key === Process::OUT ? Output\Type::output() : Output\Type::error();

                    yield [Str::of((string) $value), $type];
                }

                // we wait the process to finish after iterating over the output in
                // order to correctly close the pipes to the process when the user
                // only iterates over the output so he doesn't have to call the wait
                // function automatically. It also fix a bug where too many pipes are
                // opened (consequently preventing from running new processes) when
                // we run too many processes but never wait them to finish.
                $process->wait();
            })($process)
        ));
    }

    public function pid(): Pid
    {
        return $this->pid ?? $this->pid = new Pid($this->process->getPid());
    }

    public function output(): Output
    {
        return $this->output;
    }

    /**
     * {@inheritdoc}
     */
    public function exitCode(): ExitCode
    {
        if ($this->isRunning()) {
            throw new ProcessStillRunning;
        }

        return $this->exitCode ?? $this->exitCode = new ExitCode(
            $this->process->getExitCode()
        );
    }

    public function wait(): void
    {
        $this->process->wait();
    }

    public function isRunning(): bool
    {
        return $this->process->isRunning();
    }
}
