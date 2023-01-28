<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\Server\Process\{
    Pid,
    Output,
    ExitCode,
    TimedOut,
    Failed,
    Signaled,
    Success,
};
use Innmind\Immutable\{
    Maybe,
    Either,
};

interface Process
{
    /**
     * Depending on when you access this information the pid may or may not be
     * accessible
     *
     * Background processes' pid are never accessible
     *
     * @return Maybe<Pid>
     */
    public function pid(): Maybe;
    public function output(): Output;

    /**
     * This method returns a Success either when a foreground process returned a
     * 0 exit code or when waiting for a background process
     *
     * Waiting on a background process does nothing
     *
     * @return Either<TimedOut|Failed|Signaled, Success>
     */
    public function wait(): Either;
}
