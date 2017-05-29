<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Exception\LowestPidPossibleIsTwo;

final class Pid
{
    private $value;

    public function __construct(int $value)
    {
        if ($value < 2) {
            throw new LowestPidPossibleIsTwo; //1 is reserved by the system
        }

        $this->value = $value;
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
