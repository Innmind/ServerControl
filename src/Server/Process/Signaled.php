<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Immutable\Sequence;

final class Signaled
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
    public function output(): Sequence
    {
        return $this->output;
    }
}
