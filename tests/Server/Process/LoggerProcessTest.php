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
use Innmind\Immutable\{
    Maybe,
    Either,
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
            ->willReturn($pid = Maybe::just(new Pid(2)));

        $this->assertEquals($pid, $process->pid());
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
            ->method('wait')
            ->willReturn($expected = Either::right(Maybe::just(new ExitCode(0))));

        $this->assertEquals($expected, $process->wait());
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
            ->method('wait')
            ->willReturn($expected = Either::right(Maybe::nothing()));

        $this->assertEquals($expected, $process->wait());
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
            ->willReturn($expected = Either::left(new ProcessTimedOut));

        $this->assertEquals($expected, $process->wait());
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
