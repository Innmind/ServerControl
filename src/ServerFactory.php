<?php
declare(strict_types = 1);

namespace Innmind\Server\Control;

use Innmind\Server\Control\{
    Servers\Unix,
    Exception\UnsupportedOperatingSystem,
};

final class ServerFactory
{
    public function make(): Server
    {
        switch (PHP_OS) {
            case 'Darwin':
            case 'Linux':
                return new Unix;
        }

        throw new UnsupportedOperatingSystem;
    }

    public static function build(): Server
    {
        return (new self)->make();
    }
}
