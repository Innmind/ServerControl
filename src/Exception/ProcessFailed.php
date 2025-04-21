<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Exception;

use Innmind\Server\Control\Server\{
    Command,
    Process,
    Process\Failed,
    Process\Signaled,
    Process\TimedOut,
};

final class ProcessFailed extends RuntimeException
{
    /**
     * @internal
     */
    public function __construct(
        Command $command,
        Process $process,
        Failed|TimedOut|Signaled $error,
    ) {
        $reason = match (true) {
            $error instanceof Failed => 'failed',
            $error instanceof TimedOut => 'timed out',
            $error instanceof Signaled => 'was terminated by a signal',
        };
        $pid = $process->pid()->match(
            static fn($pid) => \sprintf(
                ' with PID "%s"',
                $pid->toInt(),
            ),
            static fn() => '',
        );

        parent::__construct(\sprintf(
            'Command "%s"%s %s',
            $command->toString(),
            $pid,
            $reason,
        ));
    }
}
