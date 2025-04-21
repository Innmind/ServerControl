<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Volumes;

/**
 * @psalm-immutable
 */
final class Name
{
    /**
     * @param non-empty-string $value
     */
    public function __construct(
        private string $value,
    ) {
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return $this->value;
    }
}
