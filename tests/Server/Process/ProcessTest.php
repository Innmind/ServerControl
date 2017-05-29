<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\{
    Server\Process\Process,
    Server\Process as ProcessInterface,
    Server\Process\Pid,
    Server\Process\ExitCode,
    Server\Process\Output,
    Server\Process\Output\Type,
    Exception\ProcessStillRunning
};
use Innmind\Immutable\Str;
use Symfony\Component\Process\Process as SfProcess;
use PHPUnit\Framework\TestCase;

class ProcessTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            ProcessInterface::class,
            new Process(
                new SfProcess('ps')
            )
        );
    }

    public function testPid()
    {
        $ps = new SfProcess('ps');
        $ps->start();
        $process = new Process($ps);

        $this->assertInstanceOf(Pid::class, $process->pid());
        $this->assertTrue($process->pid()->toInt() >= 2);
    }

    public function testOutput()
    {
        $slow = new SfProcess('php fixtures/slow.php');
        $slow->start();
        $process = new Process($slow);

        $this->assertInstanceOf(Output::class, $process->output());
        $start = time();
        $count = 0;
        $process
            ->output()
            ->foreach(function(Str $data, Type $type) use ($start, &$count) {
                $this->assertSame($count."\n", (string) $data);
                $this->assertEquals(
                    (int) (string) $data % 2 === 0 ? Type::output() : Type::error(),
                    $type
                );
                $this->assertTrue((time() - $start) >= (1 + $count));
                ++$count;
            });
        $this->assertSame(6, $count);
    }

    public function testExitCode()
    {
        $slow = new SfProcess('php fixtures/slow.php');
        $slow->start();
        $process = new Process($slow);

        try {
            $process->exitCode();
            $this->fail('it should throw an exception');
        } catch (ProcessStillRunning $e) {
            //pass
        }

        sleep(7);

        $this->assertInstanceOf(ExitCode::class, $process->exitCode());
        $this->assertSame(0, $process->exitCode()->toInt());
    }

    public function testExitCodeForFailingProcess()
    {
        $slow = new SfProcess('php fixtures/fails.php');
        $slow->start();
        $process = new Process($slow);

        sleep(1);

        $this->assertSame(1, $process->exitCode()->toInt());
    }

    public function testWait()
    {
        $slow = new SfProcess('php fixtures/slow.php');
        $slow->start();
        $process = new Process($slow);
        $this->assertSame($process, $process->wait());

        $this->assertFalse($process->isRunning());
    }

    public function testIsRunning()
    {
        $slow = new SfProcess('php fixtures/slow.php');
        $slow->start();
        $process = new Process($slow);

        $this->assertTrue($process->isRunning());
        sleep(7);
        $this->assertFalse($process->isRunning());
    }
}
