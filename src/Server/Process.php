<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server\Process\Pid,
    Server\Process\Output,
    Server\Process\ExitCode,
    Exception\ProcessStillRunning,
    Exception\ProcessTimedOut,
};

interface Process
{
    public function pid(): Pid;
    public function output(): Output;

    /**
     * @throws ProcessStillRunning
     */
    public function exitCode(): ExitCode;

    /**
     * @throws ProcessTimedOut
     */
    public function wait(): void;
    public function isRunning(): bool;
}
