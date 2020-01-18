<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\{
    Server\Process\BackgroundProcess,
    Server\Process as ProcessInterface,
    Server\Process\Pid,
    Server\Process\ExitCode,
    Server\Process\Output\StaticOutput,
    Server\Process\Output\Type,
    Exception\ProcessStillRunning,
    Exception\BackgroundProcessInformationNotAvailable,
};
use Innmind\Immutable\Str;
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

        $this->expectException(BackgroundProcessInformationNotAvailable::class);

        $process->pid();
    }

    public function testOutput()
    {
        $slow = SfProcess::fromShellCommandline('php fixtures/slow.php &');
        $slow->start();
        $process = new BackgroundProcess($slow);

        $this->assertInstanceOf(StaticOutput::class, $process->output());
        $start = time();
        $this->assertSame('', $process->output()->toString());
        $this->assertTrue((time() - $start) < 1);
    }

    public function testExitCode()
    {
        $slow = SfProcess::fromShellCommandline('php fixtures/slow.php &');
        $slow->start();
        $process = new BackgroundProcess($slow);

        $this->expectException(BackgroundProcessInformationNotAvailable::class);

        $process->exitCode();
    }

    public function testWait()
    {
        $slow = SfProcess::fromShellCommandline('php fixtures/slow.php');
        $slow->start();
        $process = new BackgroundProcess($slow);

        $this->assertSame($process, $process->wait());
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
