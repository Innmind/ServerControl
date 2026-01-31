<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Processes;

use Innmind\Server\Control\{
    Server,
    Server\Process,
    Server\Command,
    Server\Signal,
    Server\Process\Pid,
};
use Innmind\Time\{
    Clock,
    Halt,
};
use Innmind\IO\IO;
use Innmind\Url\{
    Path,
    Authority\Host,
    Authority\Port,
    Authority\UserInformation\User,
};
use Innmind\Immutable\SideEffect;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class RemoteTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testExecute()
    {
        $remote = Server::remote(
            $this->server("ssh 'foo@example.com' 'ls '\''-l'\'''"),
            User::of('foo'),
            Host::of('example.com'),
        )->processes();

        $this->assertInstanceOf(
            Process::class,
            $remote->execute(
                Command::foreground('ls')->withShortOption('l'),
            )->unwrap(),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    public function testExecuteViaSpecificPort()
    {
        $remote = Server::remote(
            $this->server("ssh '-p' '24' 'foo@example.com' 'ls '\''-l'\'''"),
            User::of('foo'),
            Host::of('example.com'),
            Port::of(24),
        )->processes();

        $this->assertInstanceOf(
            Process::class,
            $remote->execute(
                Command::foreground('ls')->withShortOption('l'),
            )->unwrap(),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    public function testExecuteWithWorkingDirectory()
    {
        $remote = Server::remote(
            $this->server("ssh 'foo@example.com' 'cd /tmp/foo && ls '\''-l'\'''"),
            User::of('foo'),
            Host::of('example.com'),
        )->processes();

        $this->assertInstanceOf(
            Process::class,
            $remote->execute(
                Command::foreground('ls')
                    ->withShortOption('l')
                    ->withWorkingDirectory(Path::of('/tmp/foo')),
            )->unwrap(),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    public function testKill()
    {
        $remote = Server::remote(
            $this->server("ssh 'foo@example.com' 'kill '\''-9'\'' '\''42'\'''"),
            User::of('foo'),
            Host::of('example.com'),
        )->processes();

        $this->assertInstanceOf(
            SideEffect::class,
            $remote->kill(new Pid(42), Signal::kill)->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    public function testKillViaSpecificPort()
    {
        $remote = Server::remote(
            $this->server("ssh '-p' '24' 'foo@example.com' 'kill '\''-9'\'' '\''42'\'''"),
            User::of('foo'),
            Host::of('example.com'),
            Port::of(24),
        )->processes();

        $this->assertInstanceOf(
            SideEffect::class,
            $remote->kill(new Pid(42), Signal::kill)->match(
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
