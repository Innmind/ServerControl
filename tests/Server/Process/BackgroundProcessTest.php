<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Server\{
    Process\BackgroundProcess,
    Process as ProcessInterface,
    Process\Pid,
    Process\ExitCode,
    Process\Output\Output,
    Process\Output\Type,
};
use Innmind\Immutable\SideEffect;
use Symfony\Component\Process\Process as SfProcess;
use PHPUnit\Framework\TestCase;

class BackgroundProcessTest extends TestCase
{
    public function testInterface()
    {
        $process = SfProcess::fromShellCommandline('ps &');
        $process->start();

        $this->assertInstanceOf(
            ProcessInterface::class,
            new BackgroundProcess($process),
        );
    }

    public function testPid()
    {
        $ps = SfProcess::fromShellCommandline('ps &');
        $ps->start();
        $process = new BackgroundProcess($ps);

        $this->assertFalse($process->pid()->match(
            static fn() => true,
            static fn() => false,
        ));
    }

    public function testOutput()
    {
        $slow = SfProcess::fromShellCommandline('php fixtures/slow.php &');
        $slow->start();
        $process = new BackgroundProcess($slow);

        $this->assertInstanceOf(Output::class, $process->output());
        $start = \time();
        $this->assertSame('', $process->output()->toString());
        $this->assertTrue((\time() - $start) < 1);
    }

    public function testWait()
    {
        $slow = SfProcess::fromShellCommandline('php fixtures/slow.php');
        $slow->start();
        $process = new BackgroundProcess($slow);

        $this->assertInstanceOf(
            SideEffect::class,
            $process
                ->wait()
                ->match(
                    static fn($e) => $e,
                    static fn($sideEffect) => $sideEffect,
                ),
        );
    }
}
