<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Processes;

use Innmind\Server\Control\{
    Server\Processes\UnixProcesses,
    Server\Processes,
    Server\Command,
    Server\Second,
    Server\Process\ForegroundProcess,
    Server\Process\BackgroundProcess,
    Server\Signal,
    Exception\ProcessTimedOut,
};
use Innmind\Stream\Readable\Stream;
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
        $start = \time();
        $process = $processes->execute(
            Command::foreground('php')->withArgument('fixtures/slow.php')
        );

        $this->assertTrue($process->isRunning());
        $this->assertInstanceOf(ForegroundProcess::class, $process);
        $process->wait();
        $this->assertTrue((\time() - $start) >= 6);
    }

    public function testExecuteInBackground()
    {
        $processes = new UnixProcesses;
        $start = \time();
        $process = $processes->execute(
            Command::background('php')->withArgument('fixtures/slow.php')
        );

        $this->assertInstanceOf(BackgroundProcess::class, $process);
        $this->assertTrue((\time() - $start) < 2);
    }

    public function testExecuteWithInput()
    {
        $processes = new UnixProcesses;
        $process = $processes->execute(
            Command::foreground('cat')->withInput(new Stream(\fopen('fixtures/symfony.log', 'r')))
        );
        $process->wait();

        $this->assertSame(
            \file_get_contents('fixtures/symfony.log'),
            $process->output()->toString(),
        );
    }

    public function testKill()
    {
        $processes = new UnixProcesses;
        $start = \time();
        $process = $processes->execute(
            Command::foreground('php')->withArgument('fixtures/slow.php')
        );

        $this->assertNull($processes->kill($process->pid(), Signal::kill()));
        \sleep(1);
        $this->assertFalse($process->isRunning());
        $this->assertTrue((\time() - $start) < 2);
    }

    public function testTimeout()
    {
        $processes = new UnixProcesses;
        $start = \time();
        $process = $processes->execute(
            Command::foreground('sleep')
                ->withArgument('1000')
                ->timeoutAfter(new Second(1)),
        );

        try {
            $process->wait();
            $this->fail('it should throw');
        } catch (\Exception $e) {
            $this->assertInstanceOf(ProcessTimedOut::class, $e);
        }

        $this->assertLessThan(3, $start - \time());
        $this->assertFalse($process->exitCode()->successful());
    }

    public function testStreamOutput()
    {
        $called = false;
        $processes = new UnixProcesses;
        $processes
            ->execute(
                Command::foreground('cat')
                    ->withArgument('fixtures/symfony.log')
                    ->streamOutput(),
            )
            ->output()
            ->foreach(static function() use (&$called) {
                $called = true;
            });

        $this->assertTrue($called);
    }

    public function testSecondCallToStreamedOutputDoesNothing()
    {
        $called = false;
        $processes = new UnixProcesses;
        $process = $processes
            ->execute(
                Command::foreground('cat')
                    ->withArgument('fixtures/symfony.log')
                    ->streamOutput(),
            );
        $process->output()->foreach(static fn() => null);
        $process
            ->output()
            ->foreach(static function() use (&$called) {
                $called = true;
            });

        $this->assertFalse($called);
    }

    public function testOutputIsNotLostByDefault()
    {
        $called = false;
        $processes = new UnixProcesses;
        $process = $processes
            ->execute(
                Command::foreground('cat')
                    ->withArgument('fixtures/symfony.log')
            );
        $process->output()->foreach(static fn() => null);
        $process
            ->output()
            ->foreach(static function() use (&$called) {
                $called = true;
            });

        $this->assertTrue($called);
    }
}
