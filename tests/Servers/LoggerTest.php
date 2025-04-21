<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Servers\Logger,
    Server,
    Server\Processes,
    Server\Processes\Unix,
    Server\Process,
    Server\Process\Pid,
    Server\Command,
    Server\Volumes,
    Server\Signal,
};
use Innmind\TimeContinuum\Earth\Clock;
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\Stream\Streams;
use Innmind\Immutable\{
    Either,
    SideEffect,
};
use Psr\Log\NullLogger;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Server::class,
            Logger::psr(
                $this->server(),
                new NullLogger,
            ),
        );
    }

    public function testProcesses()
    {
        $server = $this->server('ls');

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
        $server = $this->server('which diskutil', "diskutil 'unmount' '/dev'");

        $logger = Logger::psr(
            $server,
            new NullLogger,
        );

        $this->assertInstanceOf(
            Volumes::class,
            $logger->volumes(),
        );
        $logger->volumes()->unmount(new Volumes\Name('/dev'));
    }

    public function testReboot()
    {
        $server = $this->server('sudo shutdown -r now');

        $logger = Logger::psr(
            $server,
            new NullLogger,
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
        $server = $this->server('sudo shutdown -h now');

        $logger = Logger::psr(
            $server,
            new NullLogger,
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $logger->shutdown()->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    private function server(string ...$commands): Server
    {
        return new class($this->processes(), $this, $commands) implements Server {
            private $inner;

            public function __construct(
                private $processes,
                private $test,
                private $commands,
            ) {
            }

            public function processes(): Processes
            {
                return $this->inner ??= new class($this->processes, $this->test, $this->commands) implements Processes {
                    public function __construct(
                        private $processes,
                        private $test,
                        private $commands,
                    ) {
                    }

                    public function execute(Command $command): Process
                    {
                        $expected = \array_shift($this->commands);
                        $this->test->assertNotNull($expected);
                        $this->test->assertSame(
                            $expected,
                            $command->toString(),
                        );

                        return $this->processes->execute(Command::foreground('echo'));
                    }

                    public function kill(Pid $pid, Signal $signal): Either
                    {
                    }
                };
            }

            public function volumes(): Volumes
            {
            }

            public function reboot(): Either
            {
            }

            public function shutdown(): Either
            {
            }
        };
    }

    private function processes(): Unix
    {
        return Unix::of(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
        );
    }
}
