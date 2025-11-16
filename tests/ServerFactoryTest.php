<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control;

use Innmind\Server\Control\{
    ServerFactory,
    Server,
    Exception\UnsupportedOperatingSystem
};
use Innmind\TimeContinuum\Clock;
use Innmind\TimeWarp\Halt;
use Innmind\IO\IO;
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
            Clock::live(),
            IO::fromAmbientAuthority(),
            Halt::new(),
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
            Clock::live(),
            IO::fromAmbientAuthority(),
            Halt::new(),
        );
    }
}
