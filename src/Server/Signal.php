<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

final class Signal
{
    public const HANG_UP = 1;
    public const INTERRUPT = 2;
    public const QUIT = 3;
    public const ABORT = 6;
    public const KILL = 9;
    public const ALARM = 14;
    public const TERMINATE = 15;

    private $value;

    private function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function hangUp(): self
    {
        return new self(self::HANG_UP);
    }

    public static function interrupt(): self
    {
        return new self(self::INTERRUPT);
    }

    public static function quit(): self
    {
        return new self(self::QUIT);
    }

    public static function abort(): self
    {
        return new self(self::ABORT);
    }

    public static function kill(): self
    {
        return new self(self::KILL);
    }

    public static function alarm(): self
    {
        return new self(self::ALARM);
    }

    public static function terminate(): self
    {
        return new self(self::TERMINATE);
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
