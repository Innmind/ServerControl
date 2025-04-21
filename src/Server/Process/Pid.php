<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

/**
 * @psalm-immutable
 */
final class Pid
{
    /**
     * 1 is reserved by the system
     * @param int<2, max> $value
     */
    public function __construct(
        private int $value,
    ) {
    }

    /**
     * @return int<2, max>
     */
    public function toInt(): int
    {
        return $this->value;
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return (string) $this->value;
    }
}
