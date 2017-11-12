<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Servers\Remote,
    Server,
    Server\Processes,
    Server\Processes\RemoteProcesses,
    Server\Command
};
use Innmind\Url\Authority\{
    HostInterface,
    Host,
    Port,
    UserInformation\UserInterface,
    UserInformation\User
};
use PHPUnit\Framework\TestCase;

class RemoteTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Server::class,
            new Remote(
                $this->createMock(Server::class),
                $this->createMock(UserInterface::class),
                $this->createMock(HostInterface::class)
            )
        );
    }

    public function testProcesses()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function(Command $command): bool {
                return (string) $command === "ssh 'foo@example.com' 'ls'";
            }));

        $remote = new Remote(
            $server,
            new User('foo'),
            new Host('example.com')
        );

        $this->assertInstanceOf(
            RemoteProcesses::class,
            $remote->processes()
        );
        $remote->processes()->execute(new Command('ls'));
    }

    public function testProcessesViaSpecificPort()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function(Command $command): bool {
                return (string) $command === "ssh '-p' '42' 'foo@example.com' 'ls'";
            }));

        $remote = new Remote(
            $server,
            new User('foo'),
            new Host('example.com'),
            new Port(42)
        );

        $this->assertInstanceOf(
            RemoteProcesses::class,
            $remote->processes()
        );
        $remote->processes()->execute(new Command('ls'));
    }
}
