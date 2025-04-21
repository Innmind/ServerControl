<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control;

use Innmind\Server\Control\{
    ServerFactory,
    Server,
    Exception\UnsupportedOperatingSystem
};
use Innmind\TimeContinuum\Earth\Clock;
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\Stream\Streams;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class ServerFactoryTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testMakeUnix()
    {
        if (!\in_array(\PHP_OS, ['Darwin', 'Linux'], true)) {
            $this->assertTrue(true);

            return;
        }

        $this->assertInstanceOf(Server::class, ServerFactory::build(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
        ));
    }

    #[Group('ci')]
    #[Group('local')]
    public function testThrowWhenUnsupportedOperatingSystem()
    {
        if (\in_array(\PHP_OS, ['Darwin', 'Linux'], true)) {
            $this->assertTrue(true);

            return;
        }

        $this->expectException(UnsupportedOperatingSystem::class);

        ServerFactory::build(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
        );
    }
}
