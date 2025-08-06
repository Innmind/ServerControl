<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process\Output;

/**
 * @psalm-immutable
 */
enum Type
{
    case output;
    case error;

    #[\NoDiscard]
    public function toString(): string
    {
        return match ($this) {
            self::output => 'stdout',
            self::error => 'stderr',
        };
    }
}
