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
    /** @var int<2, max> */
    private static int $processes = 2;

    /** @var int<2, max> */
    private int $pid;
    private Success|Failed|Signaled|TimedOut $status;

    public function __construct(Success|Failed|Signaled|TimedOut $status)
    {
        $this->pid = self::$processes++;
        $this->status = $status;
    }

    /**
     * @return Maybe<Pid>
     */
    public function pid(): Maybe
    {
        return Maybe::just(new Pid($this->pid));
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
