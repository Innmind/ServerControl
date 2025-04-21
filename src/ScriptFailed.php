<?php
declare(strict_types = 1);

namespace Innmind\Server\Control;

use Innmind\Server\Control\Server\{
    Command,
    Process,
};

final class ScriptFailed
{
    /**
     * @internal
     */
    public function __construct(
        private Command $command,
        private Process $process,
        private Process\Failed|Process\TimedOut|Process\Signaled $reason,
    ) {
    }

    public function command(): Command
    {
        return $this->command;
    }

    public function process(): Process
    {
        return $this->process;
    }

    public function reason(): Process\Failed|Process\TimedOut|Process\Signaled
    {
        return $this->reason;
    }
}
