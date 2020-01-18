<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Servers\Logger,
    Server,
    Server\Processes,
    Server\Processes\LoggerProcesses,
    Server\Process,
    Server\Process\ExitCode,
    Server\Command,
    Server\Volumes,
};
use Psr\Log\{
    LoggerInterface,
    NullLogger,
};
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Server::class,
            new Logger(
                $this->createMock(Server::class),
                $this->createMock(LoggerInterface::class)
            )
        );
    }

    public function testProcesses()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function(Command $command): bool {
                return $command->toString() === 'ls';
            }));

        $logger = new Logger(
            $server,
            new NullLogger
        );

        $this->assertInstanceOf(
            LoggerProcesses::class,
            $logger->processes()
        );
        $logger->processes()->execute(Command::foreground('ls'));
    }

    public function testVolumes()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->at(0))
            ->method('execute')
            ->with($this->callback(function(Command $command): bool {
                return $command->toString() === "which diskutil";
            }))
            ->willReturn($which = $this->createMock(Process::class));
        $which
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(0));
        $processes
            ->expects($this->at(1))
            ->method('execute')
            ->with($this->callback(function(Command $command): bool {
                return $command->toString() === "diskutil 'unmount' '/dev'";
            }))
            ->willReturn($which = $this->createMock(Process::class));
        $which
            ->expects($this->once())
            ->method('exitCode')
            ->willReturn(new ExitCode(0));

        $logger = new Logger(
            $server,
            $log = $this->createMock(LoggerInterface::class),
        );
        $log
            ->expects($this->at(0))
            ->method('info')
            ->with(
                'About to execute a command',
                [
                    'command' => 'which diskutil',
                    'workingDirectory' => null,
                ],
            );

        $this->assertInstanceOf(
            Volumes::class,
            $logger->volumes()
        );
        $logger->volumes()->unmount(new Volumes\Name('/dev'));
    }
}
