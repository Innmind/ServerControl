<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server\Script,
    Server,
    Server\Command,
    Server\Processes,
    Server\Process,
    Server\Process\ExitCode,
    ProcessFailed,
    ScriptFailed,
    ProcessTimedOut,
};
use Innmind\Immutable\{
    Either,
    SideEffect,
};
use PHPUnit\Framework\TestCase;

class ScriptTest extends TestCase
{
    public function testInvokation()
    {
        $script = new Script(
            $command1 = Command::foreground('ls'),
            $command2 = Command::foreground('ls'),
        );
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $process = $this->createMock(Process::class);
        $processes
            ->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [$command1],
                [$command2],
            )
            ->willReturn($process);
        $process
            ->expects($this->any())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));

        $this->assertInstanceOf(
            SideEffect::class,
            $script($server)->match(
                static fn($sideEffect) => $sideEffect,
                static fn($e) => $e,
            ),
        );
    }

    public function testThrowOnFailure()
    {
        $script = new Script(
            $command1 = Command::foreground('ls'),
            $command2 = Command::foreground('ls'),
            $command3 = Command::foreground('ls'),
        );
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $process1 = $this->createMock(Process::class);
        $process1
            ->expects($this->any())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $process2 = $this->createMock(Process::class);
        $process2
            ->expects($this->any())
            ->method('wait')
            ->willReturn(Either::left(new ProcessFailed(new ExitCode(1))));
        $processes
            ->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [$command1],
                [$command2],
            )
            ->will($this->onConsecutiveCalls($process1, $process2));

        $e = $script($server)->match(
            static fn() => null,
            static fn($e) => $e,
        );
        $this->assertInstanceOf(ScriptFailed::class, $e);
        $this->assertSame($process2, $e->process());
        $this->assertSame($command2, $e->command());
    }

    public function testOf()
    {
        $script = Script::of('ls', 'ls');

        $this->assertInstanceOf(Script::class, $script);

        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $process = $this->createMock(Process::class);
        $process
            ->expects($this->any())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $processes
            ->expects($this->exactly(2))
            ->method('execute')
            ->with(Command::foreground('ls'))
            ->willReturn($process);

        $this->assertInstanceOf(
            SideEffect::class,
            $script($server)->match(
                static fn($sideEffect) => $sideEffect,
                static fn($e) => $e,
            ),
        );
    }

    public function testFailDueToTimeout()
    {
        $script = new Script(
            $command = Command::foreground('ls'),
        );
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($command)
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::left($expected = new ProcessTimedOut));

        $e = $script($server)->match(
            static fn() => null,
            static fn($e) => $e,
        );

        $this->assertInstanceOf(ScriptFailed::class, $e);
        $this->assertSame($process, $e->process());
        $this->assertSame($command, $e->command());
        $this->assertSame($expected, $e->reason());
    }
}
