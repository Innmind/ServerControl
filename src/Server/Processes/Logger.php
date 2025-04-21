<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Processes;

use Innmind\Server\Control\Server\{
    Processes,
    Command,
    Signal,
    Process\Pid,
};
use Innmind\Immutable\Attempt;
use Psr\Log\LoggerInterface;

final class Logger implements Processes
{
    private function __construct(
        private Processes $processes,
        private LoggerInterface $logger,
    ) {
    }

    public static function psr(Processes $processes, LoggerInterface $logger): self
    {
        return new self($processes, $logger);
    }

    #[\Override]
    public function execute(Command $command): Attempt
    {
        $this->logger->info('About to execute a command', [
            'command' => $command->toString(),
            'workingDirectory' => $command->workingDirectory()->match(
                static fn($path) => $path->toString(),
                static fn() => null,
            ),
        ]);

        return $this->processes->execute($command);
    }

    #[\Override]
    public function kill(Pid $pid, Signal $signal): Attempt
    {
        $this->logger->info('About to kill a process', [
            'pid' => $pid->toInt(),
            'signal' => $signal->toInt(),
        ]);

        return $this->processes->kill($pid, $signal);
    }
}
