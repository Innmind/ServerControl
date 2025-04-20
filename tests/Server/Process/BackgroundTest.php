<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Server\{
    Process\Background,
    Process\Unix,
    Process\Success,
    Process,
    Command,
};
use Innmind\TimeContinuum\Earth\{
    Clock,
    Period\Second,
};
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\Stream\Streams;
use Innmind\Immutable\Monoid\Concat;
use PHPUnit\Framework\TestCase;

class BackgroundTest extends TestCase
{
    public function testInterface()
    {
        $process = new Unix(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
            new Second(1),
            Command::background('ps'),
        );

        $this->assertInstanceOf(
            Process::class,
            new Background($process()),
        );
    }

    public function testPid()
    {
        $ps = new Unix(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
            new Second(1),
            Command::background('ps'),
        );
        $process = new Background($ps());

        $this->assertFalse($process->pid()->match(
            static fn() => true,
            static fn() => false,
        ));
    }

    public function testOutput()
    {
        $slow = new Unix(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
            new Second(1),
            Command::background('php fixtures/slow.php'),
        );
        $process = new Background($slow());

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

    public function testWait()
    {
        $slow = new Unix(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
            new Second(1),
            Command::background('php fixtures/slow.php'),
        );
        $process = new Background($slow());

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
