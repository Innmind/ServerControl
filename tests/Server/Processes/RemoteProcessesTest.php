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
use Innmind\Url\{
    Path,
    Authority\Host,
    Authority\Port,
    Authority\UserInformation\User
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
                User::none(),
                Host::of('example.com'),
            )
        );
    }

    public function testExecute()
    {
        $remote = new RemoteProcesses(
            $processes = $this->createMock(Processes::class),
            User::of('foo'),
            Host::of('example.com'),
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function(Command $command): bool {
                return $command->toString() === "ssh 'foo@example.com' 'ls '\''-l'\'''";
            }))
            ->willReturn($process = $this->createMock(Process::class));

        $this->assertSame(
            $process,
            $remote->execute(
                Command::foreground('ls')->withShortOption('l')
            )
        );
    }

    public function testExecuteViaSpecificPort()
    {
        $remote = new RemoteProcesses(
            $processes = $this->createMock(Processes::class),
            User::of('foo'),
            Host::of('example.com'),
            Port::of(24),
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function(Command $command): bool {
                return $command->toString() === "ssh '-p' '24' 'foo@example.com' 'ls '\''-l'\'''";
            }))
            ->willReturn($process = $this->createMock(Process::class));

        $this->assertSame(
            $process,
            $remote->execute(
                Command::foreground('ls')->withShortOption('l')
            )
        );
    }

    public function testExecuteWithWorkingDirectory()
    {
        $remote = new RemoteProcesses(
            $processes = $this->createMock(Processes::class),
            User::of('foo'),
            Host::of('example.com'),
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function(Command $command): bool {
                return $command->toString() === "ssh 'foo@example.com' 'cd /tmp/foo && ls '\''-l'\'''";
            }))
            ->willReturn($process = $this->createMock(Process::class));

        $this->assertSame(
            $process,
            $remote->execute(
                Command::foreground('ls')
                    ->withShortOption('l')
                    ->withWorkingDirectory(Path::of('/tmp/foo')),
            )
        );
    }

    public function testKill()
    {
        $remote = new RemoteProcesses(
            $processes = $this->createMock(Processes::class),
            User::of('foo'),
            Host::of('example.com'),
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function(Command $command): bool {
                return $command->toString() === "ssh 'foo@example.com' 'kill '\''-9'\'' '\''42'\'''";
            }));

        $this->assertNull($remote->kill(new Pid(42), Signal::kill()));
    }

    public function testKillViaSpecificPort()
    {
        $remote = new RemoteProcesses(
            $processes = $this->createMock(Processes::class),
            User::of('foo'),
            Host::of('example.com'),
            Port::of(24),
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function(Command $command): bool {
                return $command->toString() === "ssh '-p' '24' 'foo@example.com' 'kill '\''-9'\'' '\''42'\'''";
            }));

        $this->assertNull($remote->kill(new Pid(42), Signal::kill()));
    }
}
