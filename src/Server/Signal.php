<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

final class Signal
{
    public const HANG_UP = \SIGHUP;
    public const INTERRUPT = \SIGINT;
    public const QUIT = \SIGQUIT;
    public const ABORT = \SIGABRT;
    public const KILL = \SIGKILL;
    public const ALARM = \SIGALRM;
    public const TERMINATE = \SIGTERM;

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
