<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\{
    Server\Process,
    Server\Command,
};
use Innmind\Immutable\{
    Maybe,
    Either,
};
use Psr\Log\LoggerInterface;

final class Logger implements Process
{
    private Process $process;
    private Command $command;
    private LoggerInterface $logger;

    private function __construct(
        Process $process,
        Command $command,
        LoggerInterface $logger,
    ) {
        $this->process = $process;
        $this->command = $command;
        $this->logger = $logger;
    }

    public static function psr(
        Process $process,
        Command $command,
        LoggerInterface $logger,
    ): self {
        return new self($process, $command, $logger);
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
        return Output\Logger::psr(
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
                    Signaled::class => [
                        'Command {command} stopped due to external signal',
                        ['command' => $this->command->toString()],
                    ],
                    Failed::class => [
                        'Command {command} failed with {exitCode}',
                        [
                            'command' => $this->command->toString(),
                            'exitCode' => $e->exitCode()->toInt(),
                        ],
                    ],
                    TimedOut::class => [
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
