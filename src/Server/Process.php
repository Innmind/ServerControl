<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server\Process\Pid,
    Server\Process\Output,
    Server\Process\ExitCode,
    Exception\ProcessTimedOut,
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
     * @return Either<ProcessTimedOut, Maybe<ExitCode>>
     */
    public function wait(): Either;
    public function isRunning(): bool;
}
