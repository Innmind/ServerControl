<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

/**
 * @psalm-immutable
 */
final class ExitCode
{
    /**
     * @param int<0, 255> $value
     */
    public function __construct(
        private int $value,
    ) {
    }

    public function successful(): bool
    {
        return $this->value === 0;
    }

    /**
     * @return int<0, 255>
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
