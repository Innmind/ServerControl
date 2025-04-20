<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\Server\Process\{
    Pid,
    Output,
    TimedOut,
    Failed,
    Signaled,
    Success,
};
use Innmind\Immutable\{
    Sequence,
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

    /**
     * @return Sequence<Output\Chunk>
     */
    public function output(): Sequence;

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
