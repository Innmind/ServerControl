<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Exception;

use Innmind\Server\Control\Server\{
    Command,
    Process
};

final class ScriptFailed extends RuntimeException
{
    private $command;
    private $process;

    public function __construct(Command $command, Process $process)
    {
        parent::__construct((string) $command, $process->exitCode()->toInt());
        $this->command = $command;
        $this->process = $process;
    }

    public function command(): Command
    {
        return $this->command;
    }

    public function process(): Process
    {
        return $this->process;
    }
}
