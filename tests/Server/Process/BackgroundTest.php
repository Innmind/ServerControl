<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Server\{
    Process,
    Process\Unix,
    Process\Success,
    Command,
};
use Innmind\TimeContinuum\{
    Clock,
    Period,
};
use Innmind\TimeWarp\Halt;
use Innmind\IO\IO;
use Innmind\Immutable\Monoid\Concat;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class BackgroundTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $process = new Unix(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Halt::new(),
            Period::second(1),
            Command::background('ps'),
        );

        $this->assertInstanceOf(
            Process::class,
            Process::background($process()),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    public function testPid()
    {
        $ps = new Unix(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Halt::new(),
            Period::second(1),
            Command::background('ps'),
        );
        $process = Process::background($ps());

        $this->assertFalse($process->pid()->match(
            static fn() => true,
            static fn() => false,
        ));
    }

    #[Group('ci')]
    #[Group('local')]
    public function testOutput()
    {
        $slow = new Unix(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Halt::new(),
            Period::second(1),
            Command::background('php fixtures/slow.php'),
        );
        $process = Process::background($slow());

        $start = \time();
        $this->assertSame(
            '',
            $process
                ->output()
                ->map(static fn($chunk) => $chunk->data())
                ->fold(new Concat)
                ->toString(),
        );
        $this->assertTrue((\time() - $start) < 1);
    }

    #[Group('ci')]
    #[Group('local')]
    public function testWait()
    {
        $slow = new Unix(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Halt::new(),
            Period::second(1),
            Command::background('php fixtures/slow.php'),
        );
        $process = Process::background($slow());

        $this->assertInstanceOf(
            Success::class,
            $process
                ->wait()
                ->match(
                    static fn($success) => $success,
                    static fn() => null,
                ),
        );
    }
}
