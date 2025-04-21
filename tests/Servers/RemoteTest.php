<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Servers\Remote,
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
use Innmind\Url\Authority\{
    Host,
    Port,
    UserInformation\User
};
use Innmind\Immutable\{
    Either,
    SideEffect,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class RemoteTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Server::class,
            new Remote(
                $this->server(),
                User::none(),
                Host::none(),
            ),
        );
    }

    public function testProcesses()
    {
        $server = $this->server("ssh 'foo@example.com' 'ls'");

        $remote = new Remote(
            $server,
            User::of('foo'),
            Host::of('example.com'),
        );

        $this->assertInstanceOf(
            Processes\Remote::class,
            $remote->processes(),
        );
        $remote->processes()->execute(Command::foreground('ls'));
    }

    public function testProcessesViaSpecificPort()
    {
        $server = $this->server("ssh '-p' '42' 'foo@example.com' 'ls'");

        $remote = new Remote(
            $server,
            User::of('foo'),
            Host::of('example.com'),
            Port::of(42),
        );

        $this->assertInstanceOf(
            Processes\Remote::class,
            $remote->processes(),
        );
        $remote->processes()->execute(Command::foreground('ls'));
    }

    public function testVolumes()
    {
        $server = $this->server(
            "ssh 'foo@example.com' 'which diskutil'",
            "ssh 'foo@example.com' 'diskutil '\''unmount'\'' '\''/dev'\'''",
        );

        $remote = new Remote(
            $server,
            User::of('foo'),
            Host::of('example.com'),
        );

        $this->assertInstanceOf(
            Volumes::class,
            $remote->volumes(),
        );
        $remote->volumes()->unmount(new Volumes\Name('/dev'));
    }

    public function testReboot()
    {
        $server = $this->server("ssh 'foo@example.com' 'sudo shutdown -r now'");

        $remote = new Remote(
            $server,
            User::of('foo'),
            Host::of('example.com'),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $remote->reboot()->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    public function testShutdown()
    {
        $server = $this->server("ssh 'foo@example.com' 'sudo shutdown -h now'");

        $remote = new Remote(
            $server,
            User::of('foo'),
            Host::of('example.com'),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $remote->shutdown()->match(
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
