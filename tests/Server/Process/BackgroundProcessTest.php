<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Server\{
    Process\BackgroundProcess,
    Process\Unix,
    Process as ProcessInterface,
    Process\Output\Output,
    Command,
};
use Innmind\TimeContinuum\Earth\{
    Clock,
    ElapsedPeriod,
    Period\Second,
};
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\Stream\Watch\Select;
use Innmind\Immutable\SideEffect;
use PHPUnit\Framework\TestCase;

class BackgroundProcessTest extends TestCase
{
    public function testInterface()
    {
        $process = new Unix(
            new Clock,
            Select::timeoutAfter(new ElapsedPeriod(0)),
            new Usleep,
            new Second(1),
            Command::background('ps'),
        );

        $this->assertInstanceOf(
            ProcessInterface::class,
            new BackgroundProcess($process()),
        );
    }

    public function testPid()
    {
        $ps = new Unix(
            new Clock,
            Select::timeoutAfter(new ElapsedPeriod(0)),
            new Usleep,
            new Second(1),
            Command::background('ps'),
        );
        $process = new BackgroundProcess($ps());

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
            new Second(1),
            Command::background('php fixtures/slow.php'),
        );
        $process = new BackgroundProcess($slow());

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
            new Second(1),
            Command::background('php fixtures/slow.php'),
        );
        $process = new BackgroundProcess($slow());

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
