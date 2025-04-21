<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Server\{
    Process,
    Process\Unix,
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
use Innmind\Immutable\Monoid\Concat;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class ForegroundTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
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
            Process::foreground($ps()),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    public function testPid()
    {
        $ps = new Unix(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
            new Second(1),
            Command::foreground('ps'),
        );
        $process = Process::foreground($ps());

        $this->assertGreaterThanOrEqual(
            2,
            $process->pid()->match(
                static fn($pid) => $pid->toInt(),
                static fn() => -1,
            ),
        );
    }

    #[Group('ci')]
    #[Group('local')]
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
        $process = Process::foreground($slow());

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
        $this->assertSame(
            "0\n1\n2\n3\n4\n5\n",
            $process
                ->output()
                ->map(static fn($chunk) => $chunk->data())
                ->fold(new Concat)
                ->toString(),
        );
        $this->assertSame(6, $count);
    }

    #[Group('ci')]
    #[Group('local')]
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
        $process = Process::foreground($fail());

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

    #[Group('ci')]
    #[Group('local')]
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
        $process = Process::foreground($slow());
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

    #[Group('ci')]
    #[Group('local')]
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
        $process = Process::foreground($slow());

        $this->assertSame(
            $process->wait(),
            $process->wait(),
        );
    }

    #[Group('ci')]
    #[Group('local')]
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
        $process = Process::foreground($slow());
        $process->output()->memoize();

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

    #[Group('ci')]
    #[Group('local')]
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
        $process = Process::foreground($slow());
        $this->assertInstanceOf(
            Success::class,
            $process
                ->wait()
                ->match(
                    static fn($success) => $success,
                    static fn() => null,
                ),
        );
        $this->assertSame(
            "0\n1\n2\n3\n4\n5\n",
            $process
                ->output()
                ->map(static fn($chunk) => $chunk->data())
                ->fold(new Concat)
                ->toString(),
        );
    }
}
