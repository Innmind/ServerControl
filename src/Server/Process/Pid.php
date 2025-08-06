<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

/**
 * @psalm-immutable
 */
final class Pid
{
    /**
     * @internal
     * @param int<2, max> $value 1 is reserved by the system
     */
    public function __construct(
        private int $value,
    ) {
    }

    /**
     * @return int<2, max>
     */
    #[\NoDiscard]
    public function toInt(): int
    {
        return $this->value;
    }

    /**
     * @return non-empty-string
     */
    #[\NoDiscard]
    public function toString(): string
    {
        return (string) $this->value;
    }
}
