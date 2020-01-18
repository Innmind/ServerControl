<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\{
    Server\Process as ProcessInterface,
    Server\Process\Output\GeneratedOutput,
    Exception\ProcessStillRunning
};
use Symfony\Component\Process\Process;

final class ForegroundProcess implements ProcessInterface
{
    private $process;
    private $pid;
    private $output;
    private $exitCode;

    public function __construct(Process $process)
    {
        $this->process = $process;
        $this->output = new GeneratedOutput((function(Process $process) {
            yield from $process->getIterator();

            // we wait the process to finish after iterating over the output in
            // order to correctly close the pipes to the process when the user
            // only iterates over the output so he doesn't have to call the wait
            // function automatically. It also fix a bug where too many pipes are
            // opened (consequently preventing from running new processes) when
            // we run too many processes but never wait them to finish.
            $process->wait();
        })($process));
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

    public function wait(): ProcessInterface
    {
        $this->process->wait();

        return $this;
    }

    public function isRunning(): bool
    {
        return $this->process->isRunning();
    }
}
