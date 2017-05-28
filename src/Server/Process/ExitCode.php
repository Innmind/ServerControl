<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Exception\OutOfRangeExitCode;

final class ExitCode
{
    private $value;

    public function __construct(int $value)
    {
        if ($value < 0 || $value > 255) {
            throw new OutOfRangeExitCode;
        }

        $this->value = $value;
    }

    public function isSuccessful(): bool
    {
        return $this->value === 0;
    }

    public function toInt(): int
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
