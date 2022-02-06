<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

/**
 * @psalm-immutable
 */
enum Signal
{
    case hangUp;
    case interrupt;
    case quit;
    case abort;
    case kill;
    case alarm;
    case terminate;

    public function toInt(): int
    {
        return match ($this) {
            self::hangUp => \SIGHUP,
            self::interrupt => \SIGINT,
            self::quit => \SIGQUIT,
            self::abort => \SIGABRT,
            self::kill => \SIGKILL,
            self::alarm => \SIGALRM,
            self::terminate => \SIGTERM,
        };
    }

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return (string) $this->toInt();
    }
}
