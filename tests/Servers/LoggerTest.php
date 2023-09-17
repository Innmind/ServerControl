<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Servers\Logger,
    Server,
    Server\Processes,
    Server\Process,
    Server\Process\ExitCode,
    Server\Command,
    Server\Volumes,
};
use Innmind\Immutable\{
    Either,
    SideEffect,
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
            Logger::psr(
                $this->createMock(Server::class),
                $this->createMock(LoggerInterface::class),
            ),
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
            ->with($this->callback(static function(Command $command): bool {
                return $command->toString() === 'ls';
            }));

        $logger = Logger::psr(
            $server,
            new NullLogger,
        );

        $this->assertInstanceOf(
            Processes\Logger::class,
            $logger->processes(),
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
        $which1 = $this->createMock(Process::class);
        $which1
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $which2 = $this->createMock(Process::class);
        $which2
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $processes
            ->expects($matcher = $this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function(Command $command) use ($matcher, $which1, $which2) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertSame(
                        'which diskutil',
                        $command->toString(),
                    ),
                    2 => $this->assertSame(
                        "diskutil 'unmount' '/dev'",
                        $command->toString(),
                    ),
                };

                return match ($matcher->numberOfInvocations()) {
                    1 => $which1,
                    2 => $which2,
                };
            });

        $logger = Logger::psr(
            $server,
            $log = $this->createMock(LoggerInterface::class),
        );
        $log
            ->expects($matcher = $this->atLeast(1))
            ->method('info')
            ->willReturnCallback(function($message, $context) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    $this->assertSame('About to execute a command', $message);
                    $this->assertSame(
                        [
                            'command' => 'which diskutil',
                            'workingDirectory' => null,
                        ],
                        $context,
                    );
                }
            });

        $this->assertInstanceOf(
            Volumes::class,
            $logger->volumes(),
        );
        $logger->volumes()->unmount(new Volumes\Name('/dev'));
    }

    public function testReboot()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function(Command $command): bool {
                return $command->toString() === 'sudo shutdown -r now';
            }))
            ->willReturn($shutdown = $this->createMock(Process::class));
        $shutdown
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));

        $logger = Logger::psr(
            $server,
            $log = $this->createMock(LoggerInterface::class),
        );
        $log
            ->expects($this->once())
            ->method('info')
            ->with(
                'About to execute a command',
                [
                    'command' => 'sudo shutdown -r now',
                    'workingDirectory' => null,
                ],
            );

        $this->assertInstanceOf(
            SideEffect::class,
            $logger->reboot()->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    public function testShutdown()
    {
        $server = $this->createMock(Server::class);
        $server
            ->expects($this->once())
            ->method('processes')
            ->willReturn($processes = $this->createMock(Processes::class));
        $processes
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static function(Command $command): bool {
                return $command->toString() === 'sudo shutdown -h now';
            }))
            ->willReturn($shutdown = $this->createMock(Process::class));
        $shutdown
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));

        $logger = Logger::psr(
            $server,
            $log = $this->createMock(LoggerInterface::class),
        );
        $log
            ->expects($this->once())
            ->method('info')
            ->with(
                'About to execute a command',
                [
                    'command' => 'sudo shutdown -h now',
                    'workingDirectory' => null,
                ],
            );

        $this->assertInstanceOf(
            SideEffect::class,
            $logger->shutdown()->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }
}
