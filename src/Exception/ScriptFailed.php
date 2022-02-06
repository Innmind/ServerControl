<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Exception;

use Innmind\Server\Control\Server\{
    Command,
    Process,
};

final class ScriptFailed extends RuntimeException
{
    private Command $command;
    private Process $process;
    private ProcessFailed|ProcessTimedOut|ProcessSignaled $reason;

    public function __construct(
        Command $command,
        Process $process,
        ProcessFailed|ProcessTimedOut|ProcessSignaled $reason,
    ) {
        $this->command = $command;
        $this->process = $process;
        $this->reason = $reason;
    }

    public function command(): Command
    {
        return $this->command;
    }

    public function process(): Process
    {
        return $this->process;
    }

    public function reason(): ProcessFailed|ProcessTimedOut|ProcessSignaled
    {
        return $this->reason;
    }
}
