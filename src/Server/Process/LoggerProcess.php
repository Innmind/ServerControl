<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\{
    Server\Process,
    Server\Command,
    Exception\ProcessFailed,
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
        LoggerInterface $logger,
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
                if ($e instanceof ProcessFailed) {
                    $this->logger->warning('Command {command} failed with {exitCode}', [
                        'command' => $this->command->toString(),
                        'exitCode' => $e->exitCode()->toInt(),
                    ]);
                } else {
                    $this->logger->warning('Command {command} timed out', [
                        'command' => $this->command->toString(),
                    ]);
                }

                return $e;
            })
            ->map(function($sideEffect) {
                $this->logger->debug('Command {command} terminated correctly', [
                    'command' => $this->command->toString(),
                ]);

                return $sideEffect;
            });
    }
}
