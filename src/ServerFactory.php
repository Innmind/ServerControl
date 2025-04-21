<?php
declare(strict_types = 1);

namespace Innmind\Server\Control;

use Innmind\Server\Control\{
    Servers\Unix,
    Exception\UnsupportedOperatingSystem,
};
use Innmind\TimeContinuum\{
    Clock,
    Period,
};
use Innmind\TimeWarp\Halt;
use Innmind\IO\IO;

final class ServerFactory
{
    /**
     * @throws UnsupportedOperatingSystem For windows system
     */
    public static function build(
        Clock $clock,
        IO $io,
        Halt $halt,
        ?Period $grace = null,
    ): Server {
        switch (\PHP_OS) {
            case 'Darwin':
            case 'Linux':
                return Unix::of($clock, $io, $halt, $grace);
        }

        throw new UnsupportedOperatingSystem;
    }
}
