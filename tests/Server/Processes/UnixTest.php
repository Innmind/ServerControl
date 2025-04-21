<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Processes;

use Innmind\Server\Control\{
    Server\Processes\Unix,
    Server\Processes,
    Server\Command,
    Server\Second,
    Server\Process,
    Server\Process\TimedOut,
    Server\Signal,
};
use Innmind\Filesystem\File\Content;
use Innmind\TimeContinuum\Clock;
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\IO\IO;
use Innmind\Immutable\{
    SideEffect,
    Monoid\Concat,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class UnixTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $this->assertInstanceOf(Processes::class, Unix::of(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Usleep::new(),
        ));
    }

    #[Group('ci')]
    #[Group('local')]
    public function testExecute()
    {
        $processes = Unix::of(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Usleep::new(),
        );
        $start = \time();
        $process = $processes->execute(
            Command::foreground('php')
                ->withArgument('fixtures/slow.php')
                ->withEnvironment('PATH', $_SERVER['PATH']),
        )->unwrap();

        $this->assertInstanceOf(Process::class, $process);
        $process->wait();
        $this->assertTrue((\time() - $start) >= 6);
    }

    #[Group('ci')]
    #[Group('local')]
    public function testExecuteInBackground()
    {
        $processes = Unix::of(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Usleep::new(),
        );
        $start = \time();
        $process = $processes->execute(
            Command::background('php')
                ->withArgument('fixtures/slow.php')
                ->withEnvironment('PATH', $_SERVER['PATH']),
        )->unwrap();

        $this->assertInstanceOf(Process::class, $process);
        $this->assertLessThan(2, \time() - $start);
        \exec('ps -eo '.(\PHP_OS === 'Linux' ? 'cmd' : 'command'), $commands);
        $this->assertContains('php fixtures/slow.php', $commands);
    }

    #[Group('ci')]
    #[Group('local')]
    public function testExecuteWithInput()
    {
        $processes = Unix::of(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Usleep::new(),
        );
        $process = $processes->execute(
            Command::foreground('cat')->withInput(Content::oneShot(
                IO::fromAmbientAuthority()
                    ->streams()
                    ->acquire(\fopen('fixtures/symfony.log', 'r')),
            )),
        )->unwrap();

        $this->assertSame(
            \file_get_contents('fixtures/symfony.log'),
            $process
                ->output()
                ->map(static fn($chunk) => $chunk->data())
                ->fold(new Concat)
                ->toString(),
        );
    }

    #[Group('local')]
    public function testKill()
    {
        // For some reason this test doesn't pass for linux in the CI, the
        // kill tell it succeeded but when checking the process is killed it
        // is still running. It also sometime fail on macOS.
        // That's why it's never run in the CI
        // todo investigate more why this is happening only for linux

        $processes = Unix::of(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Usleep::new(),
        );
        $start = \time();
        $process = $processes->execute(
            Command::foreground('php')
                ->withArgument('fixtures/slow.php')
                ->withEnvironment('PATH', $_SERVER['PATH']),
        )->unwrap();

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

    #[Group('ci')]
    #[Group('local')]
    public function testTimeout()
    {
        $processes = Unix::of(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Usleep::new(),
        );
        $start = \time();
        $process = $processes->execute(
            Command::foreground('sleep')
                ->withArgument('1000')
                ->timeoutAfter(new Second(1)),
        )->unwrap();

        $this->assertInstanceOf(
            TimedOut::class,
            $process->wait()->match(
                static fn() => null,
                static fn($e) => $e,
            ),
        );

        $this->assertLessThan(3, $start - \time());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testStreamOutput()
    {
        $called = false;
        $processes = Unix::of(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Usleep::new(),
        );
        $processes
            ->execute(
                Command::foreground('cat')
                    ->withArgument('fixtures/symfony.log')
                    ->streamOutput(),
            )
            ->unwrap()
            ->output()
            ->foreach(static function() use (&$called) {
                $called = true;
            });

        $this->assertTrue($called);
    }

    #[Group('ci')]
    #[Group('local')]
    public function testSecondCallToStreamedOutputThrowsAnError()
    {
        $called = false;
        $processes = Unix::of(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Usleep::new(),
        );
        $process = $processes
            ->execute(
                Command::foreground('cat')
                    ->withArgument('fixtures/symfony.log')
                    ->streamOutput(),
            )
            ->unwrap();
        $process->output()->foreach(static fn() => null);

        $this->expectException(\LogicException::class);

        $process->output()->foreach(static fn() => null);
    }

    #[Group('ci')]
    #[Group('local')]
    public function testOutputIsNotLostByDefault()
    {
        $called = false;
        $processes = Unix::of(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Usleep::new(),
        );
        $process = $processes
            ->execute(
                Command::foreground('cat')
                    ->withArgument('fixtures/symfony.log'),
            )
            ->unwrap();
        $process->output()->foreach(static fn() => null);
        $process
            ->output()
            ->foreach(static function() use (&$called) {
                $called = true;
            });

        $this->assertTrue($called);
    }

    #[Group('ci')]
    #[Group('local')]
    public function testStopProcessEvenWhenPipesAreStillOpenAfterTheProcessBeingKilled()
    {
        @\unlink('/tmp/test-file');
        \touch('/tmp/test-file');
        $processes = Unix::of(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Usleep::new(),
        );
        $tail = $processes->execute(
            Command::foreground('tail')
                ->withShortOption('f')
                ->withArgument('/tmp/test-file'),
        )->unwrap();
        $processes->execute(
            Command::background('sleep 2 && kill')
                ->withArgument($tail->pid()->match(
                    static fn($pid) => $pid->toString(),
                    static fn() => null,
                )),
        )->unwrap();

        $tail->output()->foreach(static fn() => null);
        // when done correctly then the foreach above would run forever
        $this->assertTrue(true);
    }

    #[Group('ci')]
    #[Group('local')]
    public function testRegressionWhenProcessFinishesTooFastItsFlaggedAsFailingEvenThoughItSucceeded()
    {
        $processes = Unix::of(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Usleep::new(),
        );

        $this->assertTrue(
            $processes
                ->execute(Command::foreground('df')->withShortOption('lh'))
                ->unwrap()
                ->wait()
                ->match(
                    static fn() => true,
                    static fn() => false,
                ),
        );
    }
}
