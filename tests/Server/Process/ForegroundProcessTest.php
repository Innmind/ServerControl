<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\{
    Server\Process\ForegroundProcess,
    Server\Process as ProcessInterface,
    Server\Process\Pid,
    Server\Process\ExitCode,
    Server\Process\Output,
    Server\Process\Output\Type,
    Exception\ProcessFailed,
};
use Innmind\Immutable\{
    Str,
    SideEffect,
};
use Symfony\Component\Process\Process as SfProcess;
use PHPUnit\Framework\TestCase;

class ForegroundProcessTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            ProcessInterface::class,
            new ForegroundProcess(
                SfProcess::fromShellCommandline('ps')
            )
        );
    }

    public function testPid()
    {
        $ps = SfProcess::fromShellCommandline('ps');
        $ps->start();
        $process = new ForegroundProcess($ps);

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
        $slow = SfProcess::fromShellCommandline('php fixtures/slow.php');
        $slow->start();
        $process = new ForegroundProcess($slow);

        $this->assertInstanceOf(Output::class, $process->output());
        $start = \time();
        $count = 0;
        $process
            ->output()
            ->foreach(function(Str $data, Type $type) use ($start, &$count) {
                $this->assertSame($count."\n", $data->toString());
                $this->assertEquals(
                    (int) $data->toString() % 2 === 0 ? Type::output() : Type::error(),
                    $type
                );
                $this->assertTrue((\time() - $start) >= (1 + $count));
                ++$count;
            });
        $this->assertSame(6, $count);
    }

    public function testExitCodeForFailingProcess()
    {
        $slow = SfProcess::fromShellCommandline('php fixtures/fails.php');
        $slow->start();
        $process = new ForegroundProcess($slow);

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
        $slow = SfProcess::fromShellCommandline('php fixtures/slow.php');
        $slow->start();
        $process = new ForegroundProcess($slow);
        $this->assertInstanceOf(
            SideEffect::class,
            $process
                ->wait()
                ->match(
                    static fn($sideEffect) => $sideEffect,
                    static fn($e) => $e,
                ),
        );
    }
}
