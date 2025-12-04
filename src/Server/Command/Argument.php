<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Command;

/**
 * @psalm-immutable
 * @internal
 */
final class Argument
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = (new Str($value))->toString();
    }

    public function toString(): string
    {
        return $this->value;
    }
}
