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
    Started,
    Foreground,
    Background,
};
use Innmind\Immutable\{
    Sequence,
    Maybe,
    Either,
};

final class Process
{
    private function __construct(
        private Foreground|Background $implementation,
    ) {
    }

    /**
     * @internal
     */
    public static function foreground(Started $started, bool $streamOutput = false): self
    {
        return new self(new Foreground($started, $streamOutput));
    }

    /**
     * @internal
     */
    public static function background(Started $started): self
    {
        return new self(new Background($started));
    }

    /**
     * Depending on when you access this information the pid may or may not be
     * accessible
     *
     * Background processes' pid are never accessible
     *
     * @return Maybe<Pid>
     */
    public function pid(): Maybe
    {
        return $this->implementation->pid();
    }

    /**
     * @return Sequence<Output\Chunk>
     */
    public function output(): Sequence
    {
        return $this->implementation->output();
    }

    /**
     * This method returns a Success either when a foreground process returned a
     * 0 exit code or when waiting for a background process
     *
     * Waiting on a background process does nothing
     *
     * @return Either<TimedOut|Failed|Signaled, Success>
     */
    public function wait(): Either
    {
        return $this->implementation->wait();
    }
}
