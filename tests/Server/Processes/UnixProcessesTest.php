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
use Innmind\Immutable\SideEffect;
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

        $pid = $process->pid()->match(
            static fn($pid) => $pid,
            static fn() => null,
        );
        $this->assertInstanceOf(
            SideEffect::class,
            $processes->kill($pid, Signal::kill())->match(
                static fn() => null,
                static fn($sideEffect) => $sideEffect,
            ),
        );
        \sleep(1);
        \exec('pgrep -P '.\posix_getpid(), $pids);
        $this->assertNotContains((string) $pid->toInt(), $pids);
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

        $this->assertInstanceOf(
            ProcessTimedOut::class,
            $process->wait()->match(
                static fn($e) => $e,
                static fn() => null,
            ),
        );

        $this->assertLessThan(3, $start - \time());
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
