<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Command;

use Innmind\Immutable\Str as S;

/**
 * @psalm-immutable
 */
final class Str
{
    private string $value;

    public function __construct(string $string)
    {
        $this->value = $this->escape(S::of($string))->toString();
    }

    public function toString(): string
    {
        return $this->value;
    }

    /**
     * @see Symfony\Component\Process\Process::escapeArgument()
     */
    private function escape(S $string): S
    {
        return $string
            ->replace("'", "'\\''")
            ->prepend("'")
            ->append("'");
    }
}
