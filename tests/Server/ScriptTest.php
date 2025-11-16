<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server,
    Server\Script,
    Server\Command,
    Exception\ProcessFailed,
};
use Innmind\TimeContinuum\{
    Clock,
    Period,
};
use Innmind\TimeWarp\Halt;
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
        $script = Script::of(
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
        $script = Script::of(
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
    public function testFailDueToTimeout()
    {
        $script = Script::of(
            $command = Command::foreground('sleep 10')->timeoutAfter(
                Period::second(1),
            ),
        );

        $e = $script($this->server())->match(
            static fn() => null,
            static fn($e) => $e,
        );

        $this->assertInstanceOf(ProcessFailed::class, $e);
    }

    private function server(): Server
    {
        return Server::new(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Halt::new(),
        );
    }
}
