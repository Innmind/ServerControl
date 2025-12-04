<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Command;

/**
 * @psalm-immutable
 * @internal
 */
final class Argument
{
    public function __construct(private string $value)
    {
    }

    public function unescaped(): string
    {
        return $this->value;
    }

    public function toString(): string
    {
        return (new Str($this->value))->toString();
    }
}
