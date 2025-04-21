<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Processes;

use Innmind\Server\Control\Server\{
    Processes\Remote,
    Processes,
    Process,
    Command,
    Signal,
    Process\Pid,
};
use Innmind\TimeContinuum\Earth\Clock;
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\Stream\Streams;
use Innmind\Url\{
    Path,
    Authority\Host,
    Authority\Port,
    Authority\UserInformation\User
};
use Innmind\Immutable\{
    Either,
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
            Processes::class,
            new Remote(
                $this->processes(),
                User::none(),
                Host::of('example.com'),
            ),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    public function testExecute()
    {
        $remote = new Remote(
            $this->processes("ssh 'foo@example.com' 'ls '\''-l'\'''"),
            User::of('foo'),
            Host::of('example.com'),
        );

        $this->assertInstanceOf(
            Process::class,
            $remote->execute(
                Command::foreground('ls')->withShortOption('l'),
            ),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    public function testExecuteViaSpecificPort()
    {
        $remote = new Remote(
            $this->processes("ssh '-p' '24' 'foo@example.com' 'ls '\''-l'\'''"),
            User::of('foo'),
            Host::of('example.com'),
            Port::of(24),
        );

        $this->assertInstanceOf(
            Process::class,
            $remote->execute(
                Command::foreground('ls')->withShortOption('l'),
            ),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    public function testExecuteWithWorkingDirectory()
    {
        $remote = new Remote(
            $this->processes("ssh 'foo@example.com' 'cd /tmp/foo && ls '\''-l'\'''"),
            User::of('foo'),
            Host::of('example.com'),
        );

        $this->assertInstanceOf(
            Process::class,
            $remote->execute(
                Command::foreground('ls')
                    ->withShortOption('l')
                    ->withWorkingDirectory(Path::of('/tmp/foo')),
            ),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    public function testKill()
    {
        $remote = new Remote(
            $this->processes("ssh 'foo@example.com' 'kill '\''-9'\'' '\''42'\'''"),
            User::of('foo'),
            Host::of('example.com'),
        );

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
        $remote = new Remote(
            $this->processes("ssh '-p' '24' 'foo@example.com' 'kill '\''-9'\'' '\''42'\'''"),
            User::of('foo'),
            Host::of('example.com'),
            Port::of(24),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $remote->kill(new Pid(42), Signal::kill)->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    private function processes(string ...$commands): Processes
    {
        $processes = Processes\Unix::of(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
        );

        return new class($processes, $this, $commands) implements Processes {
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
}
