<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Processes;

use Innmind\Server\Control\Server\{
    Processes\UnixProcesses,
    Processes,
    Command,
    Process,
    Signal
};
use PHPUnit\Framework\TestCase;

class UnixProcessesTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Processes::class, new UnixProcesses);
    }

    public function testExecute()
    {
        $processes = new UnixProcesses;
        $start = time();
        $process = $processes->execute(
            (new Command('php'))->withArgument('fixtures/slow.php')
        );

        $this->assertTrue($process->isRunning());
        $this->assertInstanceOf(Process::class, $process);
        $process->wait();
        $this->assertTrue((time() - $start) >= 6);
    }

    public function testKill()
    {
        $processes = new UnixProcesses;
        $start = time();
        $process = $processes->execute(
            (new Command('php'))->withArgument('fixtures/slow.php')
        );

        $this->assertSame(
            $processes,
            $processes->kill($process->pid(), Signal::kill())
        );
        $this->assertTrue((time() - $start) < 2);
    }
}
