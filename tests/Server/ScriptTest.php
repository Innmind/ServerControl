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
    Exception\ScriptFailed
};
use PHPUnit\Framework\TestCase;

class ScriptTest extends TestCase
{
    public function testInvokation()
    {
        $script = new Script(
            $command1 = Command::foreground('ls'),
            $command2 = Command::foreground('ls')
        );
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->at(0))
            ->method('execute')
            ->with($command1)
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->any())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->expects($this->any())
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $processes
            ->expects($this->at(1))
            ->method('execute')
            ->with($command2)
            ->willReturn($process);

        $this->assertNull($script($server));
    }

    public function testThrowOnFailure()
    {
        $script = new Script(
            $command1 = Command::foreground('ls'),
            $command2 = Command::foreground('ls'),
            $command3 = Command::foreground('ls')
        );
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->at(0))
            ->method('execute')
            ->with($command1)
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->any())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->expects($this->any())
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $processes
            ->expects($this->at(1))
            ->method('execute')
            ->with($command2)
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->any())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->expects($this->any())
            ->method('exitCode')
            ->willReturn(new ExitCode(1));
        $processes
            ->expects($this->exactly(2))
            ->method('execute');

        try {
            $script($server);
            $this->fail('it should throw');
        } catch (ScriptFailed $e) {
            $this->assertSame($process, $e->process());
            $this->assertSame($command2, $e->command());
        }
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
        $processes
            ->expects($this->at(0))
            ->method('execute')
            ->with(Command::foreground('ls'))
            ->willReturn($process = $this->createMock(Process::class));
        $process
            ->expects($this->any())
            ->method('wait')
            ->will($this->returnSelf());
        $process
            ->expects($this->any())
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $processes
            ->expects($this->at(1))
            ->method('execute')
            ->with(Command::foreground('ls'))
            ->willReturn($process);

        $this->assertNull($script($server));
    }
}
