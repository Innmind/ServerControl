<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\{
    Server\Process\ForegroundProcess,
    Server\Process\Unix,
    Server\Process as ProcessInterface,
    Server\Process\Pid,
    Server\Process\Output,
    Server\Process\Output\Type,
    Server\Command,
    ProcessFailed,
};
use Innmind\TimeContinuum\Earth\{
    Clock,
    ElapsedPeriod,
    Period\Second,
};
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\Stream\Watch\Select;
use Innmind\Immutable\{
    Str,
    SideEffect,
};
use PHPUnit\Framework\TestCase;

class ForegroundProcessTest extends TestCase
{
    public function testInterface()
    {
        $ps = new Unix(
            new Clock,
            Select::timeoutAfter(new ElapsedPeriod(0)),
            new Usleep,
            new Second(1),
            Command::foreground('ps'),
        );

        $this->assertInstanceOf(
            ProcessInterface::class,
            new ForegroundProcess($ps()),
        );
    }

    public function testPid()
    {
        $ps = new Unix(
            new Clock,
            Select::timeoutAfter(new ElapsedPeriod(0)),
            new Usleep,
            new Second(1),
            Command::foreground('ps'),
        );
        $process = new ForegroundProcess($ps());

        $this->assertGreaterThanOrEqual(
            2,
            $process->pid()->match(
                static fn($pid) => $pid->toInt(),
                static fn() => -1,
            ),
        );
    }

    public function testOutput()
    {
        $slow = new Unix(
            new Clock,
            Select::timeoutAfter(new ElapsedPeriod(0)),
            new Usleep,
            new Second(1),
            Command::foreground('php fixtures/slow.php'),
        );
        $process = new ForegroundProcess($slow());

        $this->assertInstanceOf(Output::class, $process->output());
        $start = \time();
        $count = 0;
        $process
            ->output()
            ->foreach(function(Str $data, Type $type) use ($start, &$count) {
                $this->assertSame($count."\n", $data->toString());
                $this->assertEquals(
                    (int) $data->toString() % 2 === 0 ? Type::output : Type::error,
                    $type,
                );
                $this->assertTrue((\time() - $start) >= (1 + $count));
                ++$count;
            });
        $this->assertSame(6, $count);
    }

    public function testExitCodeForFailingProcess()
    {
        $fail = new Unix(
            new Clock,
            Select::timeoutAfter(new ElapsedPeriod(0)),
            new Usleep,
            new Second(1),
            Command::foreground('php fixtures/fails.php'),
        );
        $process = new ForegroundProcess($fail());

        \sleep(1);

        $return = $process->wait();

        $this->assertInstanceOf(
            ProcessFailed::class,
            $return->match(
                static fn($sideEffect) => null,
                static fn($e) => $e,
            ),
        );
        $this->assertSame(
            1,
            $return->match(
                static fn($sideEffect) => null,
                static fn($e) => $e->exitCode()->toInt(),
            ),
        );
    }

    public function testWait()
    {
        $slow = new Unix(
            new Clock,
            Select::timeoutAfter(new ElapsedPeriod(0)),
            new Usleep,
            new Second(1),
            Command::foreground('php fixtures/slow.php'),
        );
        $process = new ForegroundProcess($slow());
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
