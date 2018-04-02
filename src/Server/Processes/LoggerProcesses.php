<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Processes;

use Innmind\Server\Control\Server\{
    Processes,
    Command,
    Signal,
    Process,
    Process\Pid
};
use Psr\Log\LoggerInterface;

final class LoggerProcesses implements Processes
{
    private $processes;
    private $logger;

    public function __construct(
        Processes $processes,
        LoggerInterface $logger
    ) {
        $this->processes = $processes;
        $this->logger = $logger;
    }

    public function execute(Command $command): Process
    {
        $this->logger->info('About to execute a command', [
            'command' => $command,
            'workingDirectory' => $command->hasWorkingDirectory() ? $command->workingDirectory() : null
        ]);

        return $this->processes->execute($command);
    }

    public function kill(Pid $pid, Signal $signal): Processes
    {
        $this->logger->info('About to kill a process', [
            'pid' => $pid->toInt(),
            'signal' => $signal->toInt(),
        ]);
        $this->processes->kill($pid, $signal);

        return $this;
    }
}
