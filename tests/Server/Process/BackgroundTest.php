<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Server\{
    Process\Background,
    Process\Unix,
    Process,
    Process\Output\Output,
    Command,
};
use Innmind\TimeContinuum\Earth\{
    Clock,
    ElapsedPeriod,
    Period\Second,
};
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\Stream\{
    Watch\Select,
    Streams,
};
use Innmind\Immutable\SideEffect;
use PHPUnit\Framework\TestCase;

class BackgroundTest extends TestCase
{
    public function testInterface()
    {
        $process = new Unix(
            new Clock,
            Select::timeoutAfter(new ElapsedPeriod(0)),
            new Usleep,
            Streams::fromAmbientAuthority(),
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
            Select::timeoutAfter(new ElapsedPeriod(0)),
            new Usleep,
            Streams::fromAmbientAuthority(),
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
            Select::timeoutAfter(new ElapsedPeriod(0)),
            new Usleep,
            Streams::fromAmbientAuthority(),
            new Second(1),
            Command::background('php fixtures/slow.php'),
        );
        $process = new Background($slow());

        $this->assertInstanceOf(Output::class, $process->output());
        $start = \time();
        $this->assertSame('', $process->output()->toString());
        $this->assertTrue((\time() - $start) < 1);
    }

    public function testWait()
    {
        $slow = new Unix(
            new Clock,
            Select::timeoutAfter(new ElapsedPeriod(0)),
            new Usleep,
            Streams::fromAmbientAuthority(),
            new Second(1),
            Command::background('php fixtures/slow.php'),
        );
        $process = new Background($slow());

        $this->assertInstanceOf(
            SideEffect::class,
            $process
                ->wait()
                ->match(
                    static fn($sideEffect) => $sideEffect,
                    static fn() => null,
                ),
        );
    }
}
