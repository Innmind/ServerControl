<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Processes;

use Innmind\Server\Control\Server\{
    Processes\LoggerProcesses,
    Processes,
    Process,
    Command,
    Signal,
    Process\Pid
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Either,
    SideEffect,
};
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class LoggerProcessesTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Processes::class,
            LoggerProcesses::psr(
                $this->createMock(Processes::class),
                $this->createMock(LoggerInterface::class),
            ),
        );
    }

    public function testExecute()
    {
        $logger = LoggerProcesses::psr(
            $processes = $this->createMock(Processes::class),
            $log = $this->createMock(LoggerInterface::class),
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function(Command $command): bool {
                return $command->toString() === "ls '-l'";
            }))
            ->willReturn($this->createMock(Process::class));
        $log
            ->expects($this->once())
            ->method('info')
            ->with(
                'About to execute a command',
                [
                    'command' => "ls '-l'",
                    'workingDirectory' => null,
                ],
            );

        $this->assertInstanceOf(
            Process\LoggerProcess::class,
            $logger->execute(
                Command::foreground('ls')->withShortOption('l'),
            ),
        );
    }

    public function testExecuteWithWorkingDirectory()
    {
        $logger = LoggerProcesses::psr(
            $processes = $this->createMock(Processes::class),
            $log = $this->createMock(LoggerInterface::class),
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function(Command $command): bool {
                return $command->toString() === "ls '-l'";
            }))
            ->willReturn($this->createMock(Process::class));
        $log
            ->expects($this->once())
            ->method('info')
            ->with(
                'About to execute a command',
                [
                    'command' => "ls '-l'",
                    'workingDirectory' => '/tmp/foo',
                ],
            );

        $this->assertInstanceOf(
            Process\LoggerProcess::class,
            $logger->execute(
                Command::foreground('ls')
                    ->withShortOption('l')
                    ->withWorkingDirectory(Path::of('/tmp/foo')),
            ),
        );
    }

    public function testKill()
    {
        $logger = LoggerProcesses::psr(
            $processes = $this->createMock(Processes::class),
            $log = $this->createMock(LoggerInterface::class),
        );
        $processes
            ->expects($this->once())
            ->method('kill')
            ->with(new Pid(42), Signal::kill)
            ->willReturn($expected = Either::right(new SideEffect));
        $log
            ->expects($this->once())
            ->method('info')
            ->with(
                'About to kill a process',
                [
                    'pid' => 42,
                    'signal' => 9,
                ],
            );

        $this->assertSame($expected, $logger->kill(new Pid(42), Signal::kill));
    }
}
