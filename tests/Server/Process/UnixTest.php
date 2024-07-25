<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\{
    Server\Process\Unix,
    Server\Process\Output\Chunk,
    Server\Process\Output\Type,
    Server\Process\ExitCode,
    Server\Command,
    Server\Second as Timeout,
};
use Innmind\Filesystem\File\Content;
use Innmind\TimeContinuum\Earth\{
    Clock,
    Period\Second,
};
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\Url\Path;
use Innmind\IO\IO;
use Innmind\Stream\{
    Readable\Stream,
    Streams,
    Watch\Select,
};
use Innmind\Immutable\{
    SideEffect,
    Predicate\Instance,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class UnixTest extends TestCase
{
    use BlackBox;

    public function testSimpleOutput()
    {
        $cat = new Unix(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
            new Second(1),
            Command::foreground('echo')->withArgument('hello'),
        );
        $count = 0;
        $process = $cat();

        foreach ($process->output()->keep(Instance::of(Chunk::class))->toList() as $chunk) {
            $this->assertSame(Type::output, $chunk->type());
            $this->assertSame("hello\n", $chunk->data()->toString());
            ++$count;
        }

        $this->assertSame(1, $count);
        $this->assertGreaterThanOrEqual(2, $process->pid()->toInt());
    }

    public function testOutput()
    {
        $this
            ->forAll(
                Set\Strings::madeOf(Set\Chars::ascii()->filter(static fn($char) => $char !== '\\'))
                    ->between(1, 126),
            )
            ->then(function($echo) {
                $cat = new Unix(
                    new Clock,
                    Streams::fromAmbientAuthority(),
                    new Usleep,
                    new Second(1),
                    Command::foreground('echo')->withArgument($echo),
                );
                $process = $cat();
                $output = '';

                foreach ($process->output()->keep(Instance::of(Chunk::class))->toList() as $chunk) {
                    $output .= $chunk->data()->toString();
                }

                $this->assertSame("$echo\n", $output);
                $this->assertGreaterThanOrEqual(2, $process->pid()->toInt());
            });
    }

    public function testSlowOutput()
    {
        $slow = new Unix(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
            new Second(1),
            Command::foreground('php')
                ->withArgument('fixtures/slow.php')
                ->withEnvironment('PATH', $_SERVER['PATH']),
        );
        $process = $slow();
        $count = 0;
        $output = '';

        $this->assertGreaterThanOrEqual(2, $process->pid()->toInt());

        foreach ($process->output()->keep(Instance::of(Chunk::class))->toList() as $chunk) {
            $output .= $chunk->data()->toString();
            $this->assertSame($count % 2 === 0 ? Type::output : Type::error, $chunk->type());
            ++$count;
        }

        $this->assertSame("0\n1\n2\n3\n4\n5\n", $output);
    }

    public function testTimeoutSlowOutput()
    {
        $slow = new Unix(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
            new Second(1),
            Command::foreground('php')
                ->withArgument('fixtures/slow.php')
                ->timeoutAfter(new Timeout(2))
                ->withEnvironment('PATH', $_SERVER['PATH']),
        );
        $process = $slow();
        $count = 0;
        $output = '';
        $started = \microtime(true);

        $this->assertGreaterThanOrEqual(2, $process->pid()->toInt());

        foreach ($process->output()->keep(Instance::of(Chunk::class))->toList() as $chunk) {
            $output .= $chunk->data()->toString();
            $this->assertSame($count % 2 === 0 ? Type::output : Type::error, $chunk->type());
            ++$count;
        }

        // depending on when occur the timeout of the stream_select we may end
        // up right after the process outputed its second value
        $this->assertThat(
            $output,
            $this->logicalOr(
                $this->identicalTo("0\n"),
                $this->identicalTo("0\n1\n"),
            ),
        );
        // 3 because of the grace period
        $this->assertEqualsWithDelta(3, \microtime(true) - $started, 0.5);
    }

    public function testTimeoutWaitSlowProcess()
    {
        $slow = new Unix(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
            new Second(1),
            Command::foreground('php')
                ->withArgument('fixtures/slow.php')
                ->timeoutAfter(new Timeout(2))
                ->withEnvironment('PATH', $_SERVER['PATH']),
        );
        $process = $slow();
        $started = \microtime(true);

        $this->assertGreaterThanOrEqual(2, $process->pid()->toInt());
        $e = $process
            ->output()
            ->last()
            ->either()
            ->flatMap(static fn($result) => $result)
            ->match(
                static fn() => null,
                static fn($e) => $e,
            );
        $this->assertSame('timed-out', $e);
        // 3 because of the grace period
        $this->assertEqualsWithDelta(3, \microtime(true) - $started, 0.5);
    }

    public function testWaitSuccess()
    {
        $cat = new Unix(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
            new Second(1),
            Command::foreground('echo')->withArgument('hello'),
        );

        $value = $cat()
            ->output()
            ->last()
            ->either()
            ->flatMap(static fn($result) => $result)
            ->match(
                static fn($value) => $value,
                static fn() => null,
            );

        $this->assertInstanceOf(SideEffect::class, $value);
    }

    public function testWaitFail()
    {
        $cat = new Unix(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
            new Second(1),
            Command::foreground('php')
                ->withArgument('fixtures/fails.php')
                ->withEnvironment('PATH', $_SERVER['PATH']),
        );

        $value = $cat()
            ->output()
            ->last()
            ->either()
            ->flatMap(static fn($result) => $result)
            ->match(
                static fn() => null,
                static fn($e) => $e,
            );

        $this->assertInstanceOf(ExitCode::class, $value);
        $this->assertSame(1, $value->toInt());
    }

    public function testWithInput()
    {
        $cat = new Unix(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
            new Second(1),
            Command::foreground('cat')->withInput(Content::oneShot(
                IO::of(static fn() => Select::waitForever())->readable()->wrap(
                    Stream::of(\fopen('fixtures/symfony.log', 'r')),
                ),
            )),
        );
        $output = '';

        foreach ($cat()->output()->keep(Instance::of(Chunk::class))->toList() as $chunk) {
            $output .= $chunk->data()->toString();
        }

        $this->assertSame(
            \file_get_contents('fixtures/symfony.log'),
            $output,
        );
    }

    public function testOverwrite()
    {
        @\unlink('test.log');
        $cat = new Unix(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
            new Second(1),
            Command::foreground('cat')
                ->withInput(Content::oneShot(
                    IO::of(static fn() => Select::waitForever())->readable()->wrap(
                        Stream::of(\fopen('fixtures/symfony.log', 'r')),
                    ),
                ))
                ->overwrite(Path::of('test.log')),
        );

        $value = $cat()
            ->output()
            ->last()
            ->either()
            ->flatMap(static fn($result) => $result)
            ->match(
                static fn($value) => $value,
                static fn() => null,
            );

        $this->assertInstanceOf(SideEffect::class, $value);
        $this->assertSame(
            \file_get_contents('fixtures/symfony.log'),
            \file_get_contents('test.log'),
        );
    }
}
