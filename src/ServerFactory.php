<?php
declare(strict_types = 1);

namespace Innmind\Server\Control;

use Innmind\Server\Control\Exception\UnsupportedOperatingSystem;
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
    #[\NoDiscard]
    public static function build(
        Clock $clock,
        IO $io,
        Halt $halt,
        ?Period $grace = null,
    ): Server {
        switch (\PHP_OS) {
            case 'Darwin':
            case 'Linux':
                return Server::new($clock, $io, $halt, $grace);
        }

        throw new UnsupportedOperatingSystem;
    }
}
