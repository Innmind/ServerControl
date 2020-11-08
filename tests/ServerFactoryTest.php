<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control;

use Innmind\Server\Control\{
    ServerFactory,
    Server,
    Exception\UnsupportedOperatingSystem
};
use PHPUnit\Framework\TestCase;

class ServerFactoryTest extends TestCase
{
    public function testMakeUnix()
    {
        if (!\in_array(\PHP_OS, ['Darwin', 'Linux'], true)) {
            $this->markTestSkipped();
        }

        $this->assertInstanceOf(Server::class, (new ServerFactory)());
        $this->assertInstanceOf(Server::class, ServerFactory::build());
    }

    public function testThrowWhenUnsupportedOperatingSystem()
    {
        if (\in_array(\PHP_OS, ['Darwin', 'Linux'], true)) {
            $this->markTestSkipped();
        }

        $this->expectException(UnsupportedOperatingSystem::class);

        (new ServerFactory)();
    }
}
