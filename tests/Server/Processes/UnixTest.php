<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Processes;

use Innmind\Server\Control\{
    Server\Processes\Unix,
    Server\Processes,
    Server\Command,
    Server\Second,
    Server\Process\Foreground,
    Server\Process\Background,
    Server\Process\TimedOut,
    Server\Signal,
};
use Innmind\Filesystem\File\Content;
use Innmind\TimeContinuum\Earth\Clock;
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\IO\IO;
use Innmind\Stream\{
    Readable\Stream,
    Streams,
    Watch\Select,
};
use Innmind\Immutable\SideEffect;
use PHPUnit\Framework\TestCase;

class UnixTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Processes::class, Unix::of(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
        ));
    }

    public function testExecute()
    {
        $processes = Unix::of(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
        );
        $start = \time();
        $process = $processes->execute(
            Command::foreground('php')
                ->withArgument('fixtures/slow.php')
                ->withEnvironment('PATH', $_SERVER['PATH']),
        );

        $this->assertInstanceOf(Foreground::class, $process);
        $process->wait();
        $this->assertTrue((\time() - $start) >= 6);
    }

    public function testExecuteInBackground()
    {
        $processes = Unix::of(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
        );
        $start = \time();
        $process = $processes->execute(
            Command::background('php')
                ->withArgument('fixtures/slow.php')
                ->withEnvironment('PATH', $_SERVER['PATH']),
        );

        $this->assertInstanceOf(Background::class, $process);
        $this->assertLessThan(2, \time() - $start);
        \exec('ps -eo '.(\PHP_OS === 'Linux' ? 'cmd' : 'command'), $commands);
        $this->assertContains('php fixtures/slow.php', $commands);
    }

    public function testExecuteWithInput()
    {
        $processes = Unix::of(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
        );
        $process = $processes->execute(
            Command::foreground('cat')->withInput(Content::oneShot(
                IO::of(static fn() => Select::waitForever())->readable()->wrap(
                    Stream::of(\fopen('fixtures/symfony.log', 'r')),
                ),
            )),
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

        $processes = Unix::of(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
        );
        $start = \time();
        $process = $processes->execute(
            Command::foreground('php')
                ->withArgument('fixtures/slow.php')
                ->withEnvironment('PATH', $_SERVER['PATH']),
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
        $processes = Unix::of(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
        );
        $start = \time();
        $process = $processes->execute(
            Command::foreground('sleep')
                ->withArgument('1000')
                ->timeoutAfter(new Second(1)),
        );

        $this->assertInstanceOf(
            TimedOut::class,
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
        $processes = Unix::of(
            new Clock,
            Streams::fromAmbientAuthority(),
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
        $processes = Unix::of(
            new Clock,
            Streams::fromAmbientAuthority(),
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
        $processes = Unix::of(
            new Clock,
            Streams::fromAmbientAuthority(),
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

    public function testStopProcessEvenWhenPipesAreStillOpenAfterTheProcessBeingKilled()
    {
        @\unlink('/tmp/test-file');
        \touch('/tmp/test-file');
        $processes = Unix::of(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
        );
        $tail = $processes->execute(
            Command::foreground('tail')
                ->withShortOption('f')
                ->withArgument('/tmp/test-file'),
        );
        $processes->execute(
            Command::background('sleep 2 && kill')
                ->withArgument($tail->pid()->match(
                    static fn($pid) => $pid->toString(),
                    static fn() => null,
                )),
        );

        $tail->output()->foreach(static fn() => null);
        // when done correctly then the foreach above would run forever
        $this->assertTrue(true);
    }
}
