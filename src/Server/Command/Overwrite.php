<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Command;

use Innmind\Server\Control\Exception\LogicException;
use Innmind\Immutable\Str;

final class Overwrite
{
    private $value;

    public function __construct(string $path)
    {
        if (Str::of($path)->empty()) {
            throw new LogicException;
        }

        $this->value = '> '.new Argument($path);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
