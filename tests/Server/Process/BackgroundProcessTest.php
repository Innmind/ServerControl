<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\{
    Server\Process\BackgroundProcess,
    Server\Process as ProcessInterface,
    Server\Process\Pid,
    Server\Process\ExitCode,
    Server\Process\Output\Output,
    Server\Process\Output\Type,
    Exception\BackgroundProcessInformationNotAvailable,
};
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
            new BackgroundProcess($process)
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

        $this->assertNull(
            $process
                ->wait()
                ->map(static fn($exit) => $exit->match(
                    static fn($exit) => $exit,
                    static fn() => null,
                ))
                ->match(
                    static fn($e) => $e,
                    static fn($exit) => $exit,
                ),
        );
    }

    public function testIsRunning()
    {
        $slow = SfProcess::fromShellCommandline('php fixtures/slow.php');
        $slow->start();
        $process = new BackgroundProcess($slow);

        $this->expectException(BackgroundProcessInformationNotAvailable::class);

        $process->isRunning();
    }
}
