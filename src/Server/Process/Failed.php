<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Immutable\Sequence;

final class Failed
{
    /**
     * @internal
     * @param Sequence<Output\Chunk> $output
     */
    public function __construct(
        private ExitCode $exitCode,
        private Sequence $output,
    ) {
    }

    #[\NoDiscard]
    public function exitCode(): ExitCode
    {
        return $this->exitCode;
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
