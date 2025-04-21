<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Servers\Remote,
    Server,
    Server\Processes,
    Server\Processes\Unix,
    Server\Process\Pid,
    Server\Command,
    Server\Volumes,
    Server\Signal,
};
use Innmind\TimeContinuum\Clock;
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\IO\IO;
use Innmind\Url\Authority\{
    Host,
    Port,
    UserInformation\User
};
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class RemoteTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
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

    #[Group('ci')]
    #[Group('local')]
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

    #[Group('ci')]
    #[Group('local')]
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

    #[Group('ci')]
    #[Group('local')]
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
        $remote->volumes()->unmount(Volumes\Name::of('/dev'));
    }

    #[Group('ci')]
    #[Group('local')]
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

    #[Group('ci')]
    #[Group('local')]
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

                    public function execute(Command $command): Attempt
                    {
                        $expected = \array_shift($this->commands);
                        $this->test->assertNotNull($expected);
                        $this->test->assertSame(
                            $expected,
                            $command->toString(),
                        );

                        return $this->processes->execute(Command::foreground('echo'));
                    }

                    public function kill(Pid $pid, Signal $signal): Attempt
                    {
                    }
                };
            }

            public function volumes(): Volumes
            {
            }

            public function reboot(): Attempt
            {
            }

            public function shutdown(): Attempt
            {
            }
        };
    }

    private function processes(): Unix
    {
        return Unix::of(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Usleep::new(),
        );
    }
}
