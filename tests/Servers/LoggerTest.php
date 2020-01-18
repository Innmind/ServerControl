<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Servers\Logger,
    Server,
    Server\Processes,
    Server\Processes\LoggerProcesses,
    Server\Command
};
use Psr\Log\{
    LoggerInterface,
    NullLogger
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
        $logger->processes()->execute(new Command('ls'));
    }
}
