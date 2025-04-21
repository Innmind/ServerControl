<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server\Script,
    Server\Command,
    Server\Process,
    Server\Second,
    Servers\Unix,
    ScriptFailed,
};
use Innmind\TimeContinuum\Earth\Clock;
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\Stream\Streams;
use Innmind\Immutable\SideEffect;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class ScriptTest extends TestCase
{
    public function testInvokation()
    {
        $script = new Script(
            Command::foreground('ls'),
            Command::foreground('ls'),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $script($this->server())->match(
                static fn($sideEffect) => $sideEffect,
                static fn($e) => $e,
            ),
        );
    }

    public function testThrowOnFailure()
    {
        $script = new Script(
            $command1 = Command::foreground('ls'),
            $command2 = Command::foreground('unknown'),
            $command3 = Command::foreground('ls'),
        );

        $e = $script($this->server())->match(
            static fn() => null,
            static fn($e) => $e,
        );
        $this->assertInstanceOf(ScriptFailed::class, $e);
        $this->assertSame($command2, $e->command());
    }

    public function testOf()
    {
        $script = Script::of('ls', 'ls');

        $this->assertInstanceOf(Script::class, $script);

        $this->assertInstanceOf(
            SideEffect::class,
            $script($this->server())->match(
                static fn($sideEffect) => $sideEffect,
                static fn($e) => $e,
            ),
        );
    }

    public function testFailDueToTimeout()
    {
        $script = new Script(
            $command = Command::foreground('sleep 10')->timeoutAfter(
                new Second(1),
            ),
        );

        $e = $script($this->server())->match(
            static fn() => null,
            static fn($e) => $e,
        );

        $this->assertInstanceOf(ScriptFailed::class, $e);
        $this->assertSame($command, $e->command());
        $this->assertInstanceOf(Process\TimedOut::class, $e->reason());
    }

    private function server(): Unix
    {
        return Unix::of(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
        );
    }
}
