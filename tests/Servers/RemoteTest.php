<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Servers\Remote,
    Server,
    Server\Processes,
    Server\Process,
    Server\Command,
    Server\Volumes,
};
use Innmind\Url\Authority\{
    Host,
    Port,
    UserInformation\User
};
use Innmind\Immutable\{
    Either,
    SideEffect,
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
                User::none(),
                Host::none(),
            ),
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
            ->with($this->callback(static function(Command $command): bool {
                return $command->toString() === "ssh 'foo@example.com' 'ls'";
            }));

        $remote = new Remote(
            $server,
            User::of('foo'),
            Host::of('example.com'),
        );

        $this->assertInstanceOf(
            Processes\Remote::class,
            $remote->processes(),
        );
        $remote->processes()->execute(Command::foreground('ls'));
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
            ->with($this->callback(static function(Command $command): bool {
                return $command->toString() === "ssh '-p' '42' 'foo@example.com' 'ls'";
            }));

        $remote = new Remote(
            $server,
            User::of('foo'),
            Host::of('example.com'),
            Port::of(42),
        );

        $this->assertInstanceOf(
            Processes\Remote::class,
            $remote->processes(),
        );
        $remote->processes()->execute(Command::foreground('ls'));
    }

    public function testVolumes()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $which1 = $this->createMock(Process::class);
        $which1
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $which2 = $this->createMock(Process::class);
        $which2
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $processes
            ->expects($matcher = $this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function(Command $command) use ($matcher, $which1, $which2) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertSame(
                        "ssh 'foo@example.com' 'which diskutil'",
                        $command->toString(),
                    ),
                    2 => $this->assertSame(
                        "ssh 'foo@example.com' 'diskutil '\''unmount'\'' '\''/dev'\'''",
                        $command->toString(),
                    ),
                };

                return match ($matcher->numberOfInvocations()) {
                    1 => $which1,
                    2 => $which2,
                };
            });

        $remote = new Remote(
            $server,
            User::of('foo'),
            Host::of('example.com'),
        );

        $this->assertInstanceOf(
            Volumes::class,
            $remote->volumes(),
        );
        $remote->volumes()->unmount(new Volumes\Name('/dev'));
    }

    public function testReboot()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function(Command $command): bool {
                return $command->toString() === "ssh 'foo@example.com' 'sudo shutdown -r now'";
            }))
            ->willReturn($shutdown = $this->createMock(Process::class));
        $shutdown
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));

        $remote = new Remote(
            $server,
            User::of('foo'),
            Host::of('example.com'),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $remote->reboot()->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    public function testShutdown()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function(Command $command): bool {
                return $command->toString() === "ssh 'foo@example.com' 'sudo shutdown -h now'";
            }))
            ->willReturn($shutdown = $this->createMock(Process::class));
        $shutdown
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));

        $remote = new Remote(
            $server,
            User::of('foo'),
            Host::of('example.com'),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $remote->shutdown()->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }
}
