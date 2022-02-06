<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

/**
 * @psalm-immutable
 */
final class Second
{
    /** @var positive-int */
    private int $value;

    /**
     * @param positive-int $value
     */
    public function __construct(int $value)
    {
        $this->value = $value;
    }

    /**
     * @return positive-int
     */
    public function toInt(): int
    {
        return $this->value;
    }
}
