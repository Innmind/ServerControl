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
use PHPUnit\Framework\TestCase;

class UnixTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Server::class, Unix::of(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
        ));
    }

    public function testProcesses()
    {
        $this->assertInstanceOf(Processes::class, Unix::of(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
        )->processes());
    }

    public function testVolumes()
    {
        $this->assertInstanceOf(Volumes::class, Unix::of(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
        )->volumes());
    }
}
