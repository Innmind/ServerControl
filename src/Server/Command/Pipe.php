<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Command;

/**
 * @psalm-immutable
 */
final class Pipe implements Parameter
{
    public function toString(): string
    {
        return '|';
    }
}
