<?php
declare(strict_types = 1);

namespace Innmind\Server\Control;

use Innmind\Server\Control\{
    Servers\Unix,
    Exception\UnsupportedOperatingSystem,
};

final class ServerFactory
{
    /**
     * @throws UnsupportedOperatingSystem For windows system
     */
    public static function build(): Server
    {
        switch (\PHP_OS) {
            case 'Darwin':
            case 'Linux':
                return new Unix;
        }

        throw new UnsupportedOperatingSystem;
    }
}
