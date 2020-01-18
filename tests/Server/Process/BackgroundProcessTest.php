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
    Exception\ProcessStillRunning
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

    /**
     * @expectedException Innmind\Server\Control\Exception\BackgroundProcessInformationNotAvailable
     */
    public function testPid()
    {
        $ps = SfProcess::fromShellCommandline('ps &');
        $ps->start();
        $process = new BackgroundProcess($ps);

        $process->pid();
    }

    public function testOutput()
    {
        $slow = SfProcess::fromShellCommandline('php fixtures/slow.php &');
        $slow->start();
        $process = new BackgroundProcess($slow);

        $this->assertInstanceOf(StaticOutput::class, $process->output());
        $start = time();
        $this->assertSame('', (string) $process->output());
        $this->assertTrue((time() - $start) < 1);
    }

     /**
     * @expectedException Innmind\Server\Control\Exception\BackgroundProcessInformationNotAvailable
     */
    public function testExitCode()
    {
        $slow = SfProcess::fromShellCommandline('php fixtures/slow.php &');
        $slow->start();
        $process = new BackgroundProcess($slow);

        $process->exitCode();
    }

    public function testWait()
    {
        $slow = SfProcess::fromShellCommandline('php fixtures/slow.php');
        $slow->start();
        $process = new BackgroundProcess($slow);

        $this->assertSame($process, $process->wait());
    }

     /**
     * @expectedException Innmind\Server\Control\Exception\BackgroundProcessInformationNotAvailable
     */
    public function testIsRunning()
    {
        $slow = SfProcess::fromShellCommandline('php fixtures/slow.php');
        $slow->start();
        $process = new BackgroundProcess($slow);

        $process->isRunning();
    }
}
