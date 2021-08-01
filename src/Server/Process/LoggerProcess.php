<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Server\{
    Process,
    Command,
};
use Innmind\Immutable\{
    Maybe,
    Either,
};
use Psr\Log\LoggerInterface;

final class LoggerProcess implements Process
{
    private Process $process;
    private Command $command;
    private LoggerInterface $logger;

    public function __construct(
        Process $process,
        Command $command,
        LoggerInterface $logger
    ) {
        $this->process = $process;
        $this->command = $command;
        $this->logger = $logger;
    }

    public function pid(): Maybe
    {
        return $this
            ->process
            ->pid()
            ->map(function($pid) {
                $this->logger->debug('Command {command} is running with pid {pid}', [
                    'command' => $this->command->toString(),
                    'pid' => $pid->toInt(),
                ]);

                return $pid;
            });
    }

    public function output(): Output
    {
        return new Output\Logger(
            $this->process->output(),
            $this->command,
            $this->logger,
        );
    }

    public function wait(): Either
    {
        return $this
            ->process
            ->wait()
            ->leftMap(function($e) {
                $this->logger->warning('Command {command} timed out', [
                    'command' => $this->command->toString(),
                ]);

                return $e;
            })
            ->map(fn($exit) => $exit->map(function($exit) {
                $this->logger->debug('Command {command} terminated with {exitCode}', [
                    'command' => $this->command->toString(),
                    'exitCode' => $exit->toInt(),
                ]);

                return $exit;
            }));
    }

    public function isRunning(): bool
    {
        $isRunning = $this->process->isRunning();
        $this->logger->debug('Checking if command {command} is running', [
            'command' => $this->command->toString(),
            'running' => $isRunning,
        ]);

        return $isRunning;
    }
}
