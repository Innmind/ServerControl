<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

/**
 * @psalm-immutable
 */
final class Second
{
    /**
     * @param positive-int $value
     */
    public function __construct(
        private int $value,
    ) {
    }

    /**
     * @return positive-int
     */
    public function toInt(): int
    {
        return $this->value;
    }
}
