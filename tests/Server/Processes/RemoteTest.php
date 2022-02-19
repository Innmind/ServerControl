<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Processes;

use Innmind\Server\Control\Server\{
    Processes\Remote,
    Processes,
    Process,
    Command,
    Signal,
    Process\Pid,
    Process\ExitCode,
};
use Innmind\Url\{
    Path,
    Authority\Host,
    Authority\Port,
    Authority\UserInformation\User
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
            Processes::class,
            new Remote(
                $this->createMock(Processes::class),
                User::none(),
                Host::of('example.com'),
            ),
        );
    }

    public function testExecute()
    {
        $remote = new Remote(
            $processes = $this->createMock(Processes::class),
            User::of('foo'),
            Host::of('example.com'),
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function(Command $command): bool {
                return $command->toString() === "ssh 'foo@example.com' 'ls '\''-l'\'''";
            }))
            ->willReturn($process = $this->createMock(Process::class));

        $this->assertSame(
            $process,
            $remote->execute(
                Command::foreground('ls')->withShortOption('l'),
            ),
        );
    }

    public function testExecuteViaSpecificPort()
    {
        $remote = new Remote(
            $processes = $this->createMock(Processes::class),
            User::of('foo'),
            Host::of('example.com'),
            Port::of(24),
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function(Command $command): bool {
                return $command->toString() === "ssh '-p' '24' 'foo@example.com' 'ls '\''-l'\'''";
            }))
            ->willReturn($process = $this->createMock(Process::class));

        $this->assertSame(
            $process,
            $remote->execute(
                Command::foreground('ls')->withShortOption('l'),
            ),
        );
    }

    public function testExecuteWithWorkingDirectory()
    {
        $remote = new Remote(
            $processes = $this->createMock(Processes::class),
            User::of('foo'),
            Host::of('example.com'),
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function(Command $command): bool {
                return $command->toString() === "ssh 'foo@example.com' 'cd /tmp/foo && ls '\''-l'\'''";
            }))
            ->willReturn($process = $this->createMock(Process::class));

        $this->assertSame(
            $process,
            $remote->execute(
                Command::foreground('ls')
                    ->withShortOption('l')
                    ->withWorkingDirectory(Path::of('/tmp/foo')),
            ),
        );
    }

    public function testKill()
    {
        $remote = new Remote(
            $processes = $this->createMock(Processes::class),
            User::of('foo'),
            Host::of('example.com'),
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function(Command $command): bool {
                return $command->toString() === "ssh 'foo@example.com' 'kill '\''-9'\'' '\''42'\'''";
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));

        $this->assertInstanceOf(
            SideEffect::class,
            $remote->kill(new Pid(42), Signal::kill)->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    public function testKillViaSpecificPort()
    {
        $remote = new Remote(
            $processes = $this->createMock(Processes::class),
            User::of('foo'),
            Host::of('example.com'),
            Port::of(24),
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function(Command $command): bool {
                return $command->toString() === "ssh '-p' '24' 'foo@example.com' 'kill '\''-9'\'' '\''42'\'''";
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));

        $this->assertInstanceOf(
            SideEffect::class,
            $remote->kill(new Pid(42), Signal::kill)->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }
}
