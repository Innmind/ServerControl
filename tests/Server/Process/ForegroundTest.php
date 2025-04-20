<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Server\{
    Process\Foreground,
    Process\Unix,
    Process,
    Process\Output,
    Process\Output\Type,
    Process\Failed,
    Process\Success,
    Command,
};
use Innmind\TimeContinuum\Earth\{
    Clock,
    Period\Second,
};
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\Stream\Streams;
use PHPUnit\Framework\TestCase;

class ForegroundTest extends TestCase
{
    public function testInterface()
    {
        $ps = new Unix(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
            new Second(1),
            Command::foreground('ps'),
        );

        $this->assertInstanceOf(
            Process::class,
            new Foreground($ps()),
        );
    }

    public function testPid()
    {
        $ps = new Unix(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
            new Second(1),
            Command::foreground('ps'),
        );
        $process = new Foreground($ps());

        $this->assertGreaterThanOrEqual(
            2,
            $process->pid()->match(
                static fn($pid) => $pid->toInt(),
                static fn() => -1,
            ),
        );
    }

    public function testOutput()
    {
        $slow = new Unix(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
            new Second(1),
            Command::foreground('php fixtures/slow.php')
                ->withEnvironment('PATH', $_SERVER['PATH']),
        );
        $process = new Foreground($slow());

        $this->assertInstanceOf(Output::class, $process->output());
        $start = \time();
        $count = 0;
        $process
            ->output()
            ->foreach(function($chunk) use ($start, &$count) {
                $this->assertSame($count."\n", $chunk->data()->toString());
                $this->assertEquals(
                    (int) $chunk->data()->toString() % 2 === 0 ? Type::output : Type::error,
                    $chunk->type(),
                );
                $this->assertTrue((\time() - $start) >= (1 + $count));
                ++$count;
            });
        $this->assertSame("0\n1\n2\n3\n4\n5\n", $process->output()->toString());
        $this->assertSame(6, $count);
    }

    public function testExitCodeForFailingProcess()
    {
        $fail = new Unix(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
            new Second(1),
            Command::foreground('php fixtures/fails.php')
                ->withEnvironment('PATH', $_SERVER['PATH']),
        );
        $process = new Foreground($fail());

        \sleep(1);

        $return = $process->wait();

        $this->assertInstanceOf(
            Failed::class,
            $return->match(
                static fn($success) => null,
                static fn($e) => $e,
            ),
        );
        $this->assertSame(
            1,
            $return->match(
                static fn($success) => null,
                static fn($e) => $e->exitCode()->toInt(),
            ),
        );
        $this->assertSame(
            $process->output(),
            $return->match(
                static fn($success) => null,
                static fn($e) => $e->output(),
            ),
        );
    }

    public function testWait()
    {
        $slow = new Unix(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
            new Second(1),
            Command::foreground('php fixtures/slow.php')
                ->withEnvironment('PATH', $_SERVER['PATH']),
        );
        $process = new Foreground($slow());
        $return = $process->wait();

        $this->assertInstanceOf(
            Success::class,
            $return->match(
                static fn($success) => $success,
                static fn() => null,
            ),
        );
        $this->assertSame(
            $process->output(),
            $return->match(
                static fn($success) => $success->output(),
                static fn() => null,
            ),
        );
    }

    public function testExitStatusIsKeptInMemory()
    {
        $slow = new Unix(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
            new Second(1),
            Command::foreground('php fixtures/slow.php')
                ->withEnvironment('PATH', $_SERVER['PATH']),
        );
        $process = new Foreground($slow());

        $this->assertSame(
            $process->wait(),
            $process->wait(),
        );
    }

    public function testExitStatusIsAvailableAfterIteratingOverTheOutput()
    {
        $slow = new Unix(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
            new Second(1),
            Command::foreground('php fixtures/slow.php')
                ->withEnvironment('PATH', $_SERVER['PATH']),
        );
        $process = new Foreground($slow());
        $process->output()->toString();

        $this->assertInstanceOf(
            Success::class,
            $process
                ->wait()
                ->match(
                    static fn($success) => $success,
                    static fn() => null,
                ),
        );
    }

    public function testOutputIsAvailableAfterWaitingForExitStatus()
    {
        $slow = new Unix(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
            new Second(1),
            Command::foreground('php fixtures/slow.php')
                ->withEnvironment('PATH', $_SERVER['PATH']),
        );
        $process = new Foreground($slow());
        $this->assertInstanceOf(
            Success::class,
            $process
                ->wait()
                ->match(
                    static fn($success) => $success,
                    static fn() => null,
                ),
        );
        $this->assertSame("0\n1\n2\n3\n4\n5\n", $process->output()->toString());
    }
}
