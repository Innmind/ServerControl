<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Server\{
    Process\Logger,
    Process\Pid,
    Process\ExitCode,
    Process\Output,
    Process,
    Command,
};
use Innmind\Immutable\{
    Maybe,
    Either,
    SideEffect,
};
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Process::class,
            Logger::psr(
                $this->createMock(Process::class),
                Command::foreground('echo'),
                $this->createMock(LoggerInterface::class),
            ),
        );
    }

    public function testPid()
    {
        $process = Logger::psr(
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
        $process = Logger::psr(
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
            ->willReturn($expected = Either::right(new SideEffect));

        $this->assertEquals($expected, $process->wait());
    }

    public function testWarnFailure()
    {
        $process = Logger::psr(
            $inner = $this->createMock(Process::class),
            Command::foreground('echo'),
            $logger = $this->createMock(LoggerInterface::class),
        );
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with('Command {command} failed with {exitCode}');
        $inner
            ->expects($this->once())
            ->method('wait')
            ->willReturn($expected = Either::left(new Process\Failed(
                new ExitCode(1),
                $this->createMock(Output::class),
            )));

        $this->assertEquals($expected, $process->wait());
    }

    public function testWarnTimeouts()
    {
        $process = Logger::psr(
            $inner = $this->createMock(Process::class),
            Command::foreground('echo'),
            $logger = $this->createMock(LoggerInterface::class),
        );
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with('Command {command} timed out');
        $inner
            ->expects($this->once())
            ->method('wait')
            ->willReturn($expected = Either::left(new Process\TimedOut(
                $this->createMock(Output::class),
            )));

        $this->assertEquals($expected, $process->wait());
    }

    public function testWarnSignals()
    {
        $process = Logger::psr(
            $inner = $this->createMock(Process::class),
            Command::foreground('echo'),
            $logger = $this->createMock(LoggerInterface::class),
        );
        $logger
            ->expects($this->once())
            ->method('warning')
            ->with('Command {command} stopped due to external signal');
        $inner
            ->expects($this->once())
            ->method('wait')
            ->willReturn($expected = Either::left(new Process\Signaled(
                $this->createMock(Output::class),
            )));

        $this->assertEquals($expected, $process->wait());
    }

    public function testOutput()
    {
        $process = Logger::psr(
            $this->createMock(Process::class),
            Command::foreground('echo'),
            $this->createMock(LoggerInterface::class),
        );

        $this->assertInstanceOf(Output\Logger::class, $process->output());
    }
}
