<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Processes;

use Innmind\Server\Control\Server\{
    Processes\RemoteProcesses,
    Processes,
    Process,
    Command,
    Signal,
    Process\Pid
};
use Innmind\Url\Authority\{
    HostInterface,
    PortInterface,
    Host,
    Port,
    UserInformation\UserInterface,
    UserInformation\User
};
use PHPUnit\Framework\TestCase;

class RemoteProcessesTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Processes::class,
            new RemoteProcesses(
                $this->createMock(Processes::class),
                $this->createMock(UserInterface::class),
                $this->createMock(HostInterface::class)
            )
        );
    }

    public function testExecute()
    {
        $remote = new RemoteProcesses(
            $processes = $this->createMock(Processes::class),
            new User('foo'),
            new Host('example.com')
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function(Command $command): bool {
                return (string) $command === 'ssh foo@example.com ls -l';
            }))
            ->willReturn($process = $this->createMock(Process::class));

        $this->assertSame(
            $process,
            $remote->execute(
                (new Command('ls'))->withShortOption('l')
            )
        );
    }

    public function testExecuteViaSpecificPort()
    {
        $remote = new RemoteProcesses(
            $processes = $this->createMock(Processes::class),
            new User('foo'),
            new Host('example.com'),
            new Port(24)
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function(Command $command): bool {
                return (string) $command === 'ssh -p 24 foo@example.com ls -l';
            }))
            ->willReturn($process = $this->createMock(Process::class));

        $this->assertSame(
            $process,
            $remote->execute(
                (new Command('ls'))->withShortOption('l')
            )
        );
    }

    public function testKill()
    {
        $remote = new RemoteProcesses(
            $processes = $this->createMock(Processes::class),
            new User('foo'),
            new Host('example.com')
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function(Command $command): bool {
                return (string) $command === 'ssh foo@example.com kill -9 42';
            }));

        $this->assertSame(
            $remote,
            $remote->kill(new Pid(42), Signal::kill())
        );
    }

    public function testKillViaSpecificPort()
    {
        $remote = new RemoteProcesses(
            $processes = $this->createMock(Processes::class),
            new User('foo'),
            new Host('example.com'),
            new Port(24)
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function(Command $command): bool {
                return (string) $command === 'ssh -p 24 foo@example.com kill -9 42';
            }));

        $this->assertSame(
            $remote,
            $remote->kill(new Pid(42), Signal::kill())
        );
    }
}
