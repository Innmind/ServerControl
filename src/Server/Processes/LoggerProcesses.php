<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Processes;

use Innmind\Server\Control\Server\{
    Processes,
    Command,
    Signal,
    Process,
    Process\Pid,
};
use Innmind\Immutable\Either;
use Psr\Log\LoggerInterface;

final class LoggerProcesses implements Processes
{
    private Processes $processes;
    private LoggerInterface $logger;

    private function __construct(
        Processes $processes,
        LoggerInterface $logger,
    ) {
        $this->processes = $processes;
        $this->logger = $logger;
    }

    public static function psr(Processes $processes, LoggerInterface $logger): self
    {
        return new self($processes, $logger);
    }

    public function execute(Command $command): Process
    {
        $this->logger->info('About to execute a command', [
            'command' => $command->toString(),
            'workingDirectory' => $command->workingDirectory()->match(
                static fn($path) => $path->toString(),
                static fn() => null,
            ),
        ]);

        return Process\LoggerProcess::psr(
            $this->processes->execute($command),
            $command,
            $this->logger,
        );
    }

    public function kill(Pid $pid, Signal $signal): Either
    {
        $this->logger->info('About to kill a process', [
            'pid' => $pid->toInt(),
            'signal' => $signal->toInt(),
        ]);

        return $this->processes->kill($pid, $signal);
    }
}
