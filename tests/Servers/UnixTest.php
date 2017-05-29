<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Servers\Unix,
    Server,
    Server\Processes
};
use PHPUnit\Framework\TestCase;

class UnixTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Server::class, new Unix);
    }

    public function testProcesses()
    {
        $this->assertInstanceOf(Processes::class, (new Unix)->processes());
    }
}
