<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server\Script,
    Server\Command,
    Server\Second,
    Servers\Unix,
    Exception\ProcessFailed,
};
use Innmind\TimeContinuum\Clock;
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\IO\IO;
use Innmind\Immutable\SideEffect;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class ScriptTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
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

    #[Group('ci')]
    #[Group('local')]
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
        $this->assertInstanceOf(ProcessFailed::class, $e);
    }

    #[Group('ci')]
    #[Group('local')]
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

    #[Group('ci')]
    #[Group('local')]
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

        $this->assertInstanceOf(ProcessFailed::class, $e);
    }

    private function server(): Unix
    {
        return Unix::of(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Usleep::new(),
        );
    }
}
