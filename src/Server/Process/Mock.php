<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Server\Process\Output\Chunk;
use Innmind\Immutable\{
    Sequence,
    Maybe,
    Either,
};

/**
 * @internal
 */
final class Mock
{
    /**
     * @param ?int<2, max> $pid
     */
    public function __construct(
        private ?int $pid,
        private Success|Failed|Signaled|TimedOut $status,
    ) {
    }

    /**
     * @return Maybe<Pid>
     */
    public function pid(): Maybe
    {
        return Maybe::of($this->pid)->map(static fn($pid) => new Pid($pid));
    }

    /**
     * @return Sequence<Chunk>
     */
    public function output(): Sequence
    {
        return $this->status->output();
    }

    /**
     * @return Either<TimedOut|Failed|Signaled, Success>
     */
    public function wait(): Either
    {
        if ($this->status instanceof Success) {
            return Either::right($this->status);
        }

        return Either::left($this->status);
    }
}
