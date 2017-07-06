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
        $this->output = new GeneratedOutput($process->getIterator());
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
