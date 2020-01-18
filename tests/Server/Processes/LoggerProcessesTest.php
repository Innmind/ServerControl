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
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class LoggerProcessesTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Processes::class,
            new LoggerProcesses(
                $this->createMock(Processes::class),
                $this->createMock(LoggerInterface::class)
            )
        );
    }

    public function testExecute()
    {
        $logger = new LoggerProcesses(
            $processes = $this->createMock(Processes::class),
            $log = $this->createMock(LoggerInterface::class)
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function(Command $command): bool {
                return $command->toString() === "ls '-l'";
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $log
            ->expects($this->once())
            ->method('info')
            ->with(
                'About to execute a command',
                [
                    'command' => "ls '-l'",
                    'workingDirectory' => null,
                ]
            );

        $this->assertSame(
            $process,
            $logger->execute(
                (new Command('ls'))->withShortOption('l')
            )
        );
    }

    public function testExecuteWithWorkingDirectory()
    {
        $logger = new LoggerProcesses(
            $processes = $this->createMock(Processes::class),
            $log = $this->createMock(LoggerInterface::class)
        );
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function(Command $command): bool {
                return $command->toString() === "ls '-l'";
            }))
            ->willReturn($process = $this->createMock(Process::class));
        $log
            ->expects($this->once())
            ->method('info')
            ->with(
                'About to execute a command',
                [
                    'command' => "ls '-l'",
                    'workingDirectory' => '/tmp/foo',
                ]
            );

        $this->assertSame(
            $process,
            $logger->execute(
                (new Command('ls'))
                    ->withShortOption('l')
                    ->withWorkingDirectory('/tmp/foo')
            )
        );
    }

    public function testKill()
    {
        $logger = new LoggerProcesses(
            $processes = $this->createMock(Processes::class),
            $log = $this->createMock(LoggerInterface::class)
        );
        $processes
            ->expects($this->once())
            ->method('kill')
            ->with(new Pid(42), Signal::kill());
        $log
            ->expects($this->once())
            ->method('info')
            ->with(
                'About to kill a process',
                [
                    'pid' => 42,
                    'signal' => 9,
                ]
            );

        $this->assertSame(
            $logger,
            $logger->kill(new Pid(42), Signal::kill())
        );
    }
}
