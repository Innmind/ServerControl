<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Servers\Unix,
    Server,
    Server\Processes,
    Server\Volumes,
};
use Innmind\TimeContinuum\Clock;
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\IO\IO;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class UnixTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $this->assertInstanceOf(Server::class, Unix::of(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Usleep::new(),
        ));
    }

    #[Group('ci')]
    #[Group('local')]
    public function testProcesses()
    {
        $this->assertInstanceOf(Processes::class, Unix::of(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Usleep::new(),
        )->processes());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testVolumes()
    {
        $this->assertInstanceOf(Volumes::class, Unix::of(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Usleep::new(),
        )->volumes());
    }
}
