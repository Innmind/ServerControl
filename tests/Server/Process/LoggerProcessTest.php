<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\{
    Server\Process\LoggerProcess,
    Server\Process\Pid,
    Server\Process\ExitCode,
    Server\Process\Output,
    Server\Process,
    Server\Command,
    Exception\ProcessTimedOut,
};
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class LoggerProcessTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Process::class,
            new LoggerProcess(
                $this->createMock(Process::class),
                Command::foreground('echo'),
                $this->createMock(LoggerInterface::class),
            ),
        );
    }

    public function testPid()
    {
        $process = new LoggerProcess(
            $inner = $this->createMock(Process::class),
            Command::foreground('echo'),
            $logger = $this->createMock(LoggerInterface::class),
        );
        $logger
            ->expects($this->once())
            ->method('debug');
        $inner
            ->expects($this->once())
            ->method('pid')
            ->willReturn($pid = new Pid(2));

        $this->assertSame($pid, $process->pid());
    }

    public function testExitCode()
    {
        $process = new LoggerProcess(
            $inner = $this->createMock(Process::class),
            Command::foreground('echo'),
            $logger = $this->createMock(LoggerInterface::class),
        );
        $logger
            ->expects($this->once())
            ->method('debug');
        $inner
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn($exitCode = new ExitCode(0));

        $this->assertSame($exitCode, $process->exitCode());
    }

    public function testIsRunning()
    {
        $process = new LoggerProcess(
            $inner = $this->createMock(Process::class),
            Command::foreground('echo'),
            $logger = $this->createMock(LoggerInterface::class),
        );
        $logger
            ->expects($this->once())
            ->method('debug');
        $inner
            ->expects($this->once())
            ->method('isRunning')
            ->willReturn(true);

        $this->assertTrue($process->isRunning());
    }

    public function testDoesntLogWhenWaitingWithoutTimeout()
    {
        $process = new LoggerProcess(
            $inner = $this->createMock(Process::class),
            Command::foreground('echo'),
            $logger = $this->createMock(LoggerInterface::class),
        );
        $logger
            ->expects($this->never())
            ->method('warning');
        $inner
            ->expects($this->once())
            ->method('wait');

        $this->assertNull($process->wait());
    }

    public function testWarnTimeouts()
    {
        $process = new LoggerProcess(
            $inner = $this->createMock(Process::class),
            Command::foreground('echo'),
            $logger = $this->createMock(LoggerInterface::class),
        );
        $logger
            ->expects($this->once())
            ->method('warning');
        $inner
            ->expects($this->once())
            ->method('wait')
            ->will($this->throwException($expected = new ProcessTimedOut));

        try {
            $process->wait();
            $this->fail('it should throw');
        } catch (\Exception $e) {
            $this->assertSame($expected, $e);
        }
    }

    public function testOutput()
    {
        $process = new LoggerProcess(
            $this->createMock(Process::class),
            Command::foreground('echo'),
            $this->createMock(LoggerInterface::class),
        );

        $this->assertInstanceOf(Output\Logger::class, $process->output());
    }
}
