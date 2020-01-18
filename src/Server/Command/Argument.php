<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Command;

final class Argument
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = (string) new Str($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
