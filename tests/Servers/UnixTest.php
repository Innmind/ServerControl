<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Servers\Unix,
    Server,
    Server\Processes,
    Server\Volumes,
};
use Innmind\TimeContinuum\Earth\Clock;
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\Stream\Streams;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class UnixTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $this->assertInstanceOf(Server::class, Unix::of(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
        ));
    }

    #[Group('ci')]
    #[Group('local')]
    public function testProcesses()
    {
        $this->assertInstanceOf(Processes::class, Unix::of(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
        )->processes());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testVolumes()
    {
        $this->assertInstanceOf(Volumes::class, Unix::of(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
        )->volumes());
    }
}
