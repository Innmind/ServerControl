<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Command,
    Server\Volumes,
};
use Innmind\Time\{
    Clock,
    Halt,
};
use Innmind\IO\IO;
use Innmind\Url\Authority\{
    Host,
    Port,
    UserInformation\User
};
use Innmind\Immutable\SideEffect;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class RemoteTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testProcesses()
    {
        $server = $this->server("ssh 'foo@example.com' 'ls'");

        $remote = Server::remote(
            $server,
            User::of('foo'),
            Host::of('example.com'),
        );

        $this->assertInstanceOf(
            Processes::class,
            $remote->processes(),
        );
        $remote->processes()->execute(Command::foreground('ls'));
    }

    #[Group('ci')]
    #[Group('local')]
    public function testProcessesViaSpecificPort()
    {
        $server = $this->server("ssh '-p' '42' 'foo@example.com' 'ls'");

        $remote = Server::remote(
            $server,
            User::of('foo'),
            Host::of('example.com'),
            Port::of(42),
        );

        $this->assertInstanceOf(
            Processes::class,
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

        $remote = Server::remote(
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

        $remote = Server::remote(
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

        $remote = Server::remote(
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
        $processes = Server::new(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Halt::new(),
        )->processes();

        return Server::via(
            function($command) use ($processes, &$commands) {
                $expected = \array_shift($commands);
                $this->assertNotNull($expected);
                $this->assertSame(
                    $expected,
                    $command->toString(),
                );

                return $processes->execute(Command::foreground('echo'));
            },
        );
    }
}
