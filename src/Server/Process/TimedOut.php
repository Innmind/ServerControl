<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
final class TimedOut
{
    /**
     * @internal
     * @param Sequence<Output\Chunk> $output
     */
    public function __construct(
        private Sequence $output,
    ) {
    }

    /**
     * @return Sequence<Output\Chunk>
     */
    #[\NoDiscard]
    public function output(): Sequence
    {
        return $this->output;
    }
}
