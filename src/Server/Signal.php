<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

final class Signal
{
    private const HANG_UP = \SIGHUP;
    private const INTERRUPT = \SIGINT;
    private const QUIT = \SIGQUIT;
    private const ABORT = \SIGABRT;
    private const KILL = \SIGKILL;
    private const ALARM = \SIGALRM;
    private const TERMINATE = \SIGTERM;

    private int $value;

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

    public function toString(): string
    {
        return (string) $this->value;
    }
}
