<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Command;

use Innmind\Immutable\Str as S;

/**
 * @internal
 */
final class Str
{
    /**
     * @psalm-pure
     * @internal
     * @see Symfony\Component\Process\Process::escapeArgument()
     */
    public static function escape(string $string): string
    {
        return S::of($string)
            ->replace("'", "'\\''")
            ->prepend("'")
            ->append("'")
            ->toString();
    }
}
