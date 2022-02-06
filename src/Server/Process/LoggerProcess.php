<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\{
    Server\Process,
    Server\Command,
    ProcessFailed,
    ProcessSignaled,
    ProcessTimedOut,
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
                [$message, $context] = match (\get_class($e)) {
                    ProcessSignaled::class => [
                        'Command {command} stopped due to external signal',
                        ['command' => $this->command->toString()],
                    ],
                    ProcessFailed::class => [
                        'Command {command} failed with {exitCode}',
                        [
                            'command' => $this->command->toString(),
                            'exitCode' => $e->exitCode()->toInt(),
                        ],
                    ],
                    ProcessTimedOut::class => [
                        'Command {command} timed out',
                        ['command' => $this->command->toString()],
                    ],
                };
                $this->logger->warning($message, $context);

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
