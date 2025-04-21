<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Exception;

use Innmind\Server\Control\Server\Process\{
    Failed,
    Signaled,
    TimedOut,
};

final class ProcessFailed extends RuntimeException
{
    /**
     * @internal
     */
    public function __construct(Failed|TimedOut|Signaled $error)
    {
        $message = match (true) {
            $error instanceof Failed => 'Process failed',
            $error instanceof TimedOut => 'Process timed out',
            $error instanceof Signaled => 'Process terminated by a signal',
        };

        parent::__construct($message);
    }
}
