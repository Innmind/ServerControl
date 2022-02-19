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
    ProcessTimedOut,
};
use Innmind\TimeContinuum\Earth\Clock;
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\Stream\{
    Readable\Stream,
    Watch\Select,
};
use Innmind\Immutable\SideEffect;
use PHPUnit\Framework\TestCase;

class UnixProcessesTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Processes::class, UnixProcesses::of(
            new Clock,
            Select::timeoutAfter(...),
            new Usleep,
        ));
    }

    public function testExecute()
    {
        $processes = UnixProcesses::of(
            new Clock,
            Select::timeoutAfter(...),
            new Usleep,
        );
        $start = \time();
        $process = $processes->execute(
            Command::foreground('php')->withArgument('fixtures/slow.php'),
        );

        $this->assertInstanceOf(ForegroundProcess::class, $process);
        $process->wait();
        $this->assertTrue((\time() - $start) >= 6);
    }

    public function testExecuteInBackground()
    {
        $processes = UnixProcesses::of(
            new Clock,
            Select::timeoutAfter(...),
            new Usleep,
        );
        $start = \time();
        $process = $processes->execute(
            Command::background('php')->withArgument('fixtures/slow.php'),
        );

        $this->assertInstanceOf(BackgroundProcess::class, $process);
        $this->assertLessThan(2, \time() - $start);
    }

    public function testExecuteWithInput()
    {
        $processes = UnixProcesses::of(
            new Clock,
            Select::timeoutAfter(...),
            new Usleep,
        );
        $process = $processes->execute(
            Command::foreground('cat')->withInput(Stream::of(\fopen('fixtures/symfony.log', 'r'))),
        );

        $this->assertSame(
            \file_get_contents('fixtures/symfony.log'),
            $process->output()->toString(),
        );
    }

    public function testKill()
    {
        if (\getenv('CI') && \PHP_OS === 'Linux') {
            // for some reason this test doesn't pass for linux in the CI, the
            // kill tell it succeeded but when checking the process is killed it
            // is still running
            // todo investigate more why this is happening only for linux
            $this->markTestSkipped();
        }

        $processes = UnixProcesses::of(
            new Clock,
            Select::timeoutAfter(...),
            new Usleep,
        );
        $start = \time();
        $process = $processes->execute(
            Command::foreground('php')->withArgument('fixtures/slow.php'),
        );

        $pid = $process->pid()->match(
            static fn($pid) => $pid,
            static fn() => null,
        );
        $this->assertInstanceOf(
            SideEffect::class,
            $processes->kill($pid, Signal::kill)->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
        \sleep(1);
        \exec('pgrep -P '.\posix_getpid(), $pids);
        $this->assertNotContains((string) $pid->toInt(), $pids);
        $this->assertTrue((\time() - $start) < 2);
    }

    public function testTimeout()
    {
        $processes = UnixProcesses::of(
            new Clock,
            Select::timeoutAfter(...),
            new Usleep,
        );
        $start = \time();
        $process = $processes->execute(
            Command::foreground('sleep')
                ->withArgument('1000')
                ->timeoutAfter(new Second(1)),
        );

        $this->assertInstanceOf(
            ProcessTimedOut::class,
            $process->wait()->match(
                static fn() => null,
                static fn($e) => $e,
            ),
        );

        $this->assertLessThan(3, $start - \time());
    }

    public function testStreamOutput()
    {
        $called = false;
        $processes = UnixProcesses::of(
            new Clock,
            Select::timeoutAfter(...),
            new Usleep,
        );
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

    public function testSecondCallToStreamedOutputThrowsAnError()
    {
        $called = false;
        $processes = UnixProcesses::of(
            new Clock,
            Select::timeoutAfter(...),
            new Usleep,
        );
        $process = $processes
            ->execute(
                Command::foreground('cat')
                    ->withArgument('fixtures/symfony.log')
                    ->streamOutput(),
            );
        $process->output()->foreach(static fn() => null);

        $this->expectException(\LogicException::class);

        $process->output()->foreach(static fn() => null);
    }

    public function testOutputIsNotLostByDefault()
    {
        $called = false;
        $processes = UnixProcesses::of(
            new Clock,
            Select::timeoutAfter(...),
            new Usleep,
        );
        $process = $processes
            ->execute(
                Command::foreground('cat')
                    ->withArgument('fixtures/symfony.log'),
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
