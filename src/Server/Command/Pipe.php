<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Command;

/**
 * @psalm-immutable
 * @internal
 */
final class Pipe implements Parameter
{
    #[\Override]
    public function toString(): string
    {
        return '|';
    }
}
