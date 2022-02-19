<?php
declare(strict_types = 1);

namespace Innmind\Server\Control;

use Innmind\Server\Control\{
    Servers\Unix,
    Exception\UnsupportedOperatingSystem,
};
use Innmind\TimeContinuum\{
    Clock,
    ElapsedPeriod,
    Period,
};
use Innmind\TimeWarp\Halt;
use Innmind\Stream\Watch;

final class ServerFactory
{
    /**
     * @param callable(ElapsedPeriod): Watch $watch
     *
     * @throws UnsupportedOperatingSystem For windows system
     */
    public static function build(
        Clock $clock,
        callable $watch,
        Halt $halt,
        Period $grace = null,
    ): Server {
        switch (\PHP_OS) {
            case 'Darwin':
            case 'Linux':
                return Unix::of($clock, $watch, $halt, $grace);
        }

        throw new UnsupportedOperatingSystem;
    }
}
