<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Server\{
    Process\Unix,
    Process\Output\Chunk,
    Process\Output\Type,
    Process\ExitCode,
    Command,
};
use Innmind\Filesystem\File\Content;
use Innmind\Time\{
    Clock,
    Period,
    Halt,
};
use Innmind\Url\Path;
use Innmind\IO\IO;
use Innmind\Immutable\{
    SideEffect,
    Predicate\Instance,
};
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};
use PHPUnit\Framework\Attributes\Group;

class UnixTest extends TestCase
{
    use BlackBox;

    #[Group('ci')]
    #[Group('local')]
    public function testSimpleOutput()
    {
        $cat = new Unix(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Halt::new(),
            Period::second(1),
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

    #[Group('ci')]
    #[Group('local')]
    public function testOutput(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::strings()
                    ->madeOf(Set::strings()->chars()->ascii()->filter(static fn($char) => $char !== '\\'))
                    ->between(1, 126),
            )
            ->prove(function($echo) {
                $cat = new Unix(
                    Clock::live(),
                    IO::fromAmbientAuthority(),
                    Halt::new(),
                    Period::second(1),
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

    #[Group('ci')]
    #[Group('local')]
    public function testSlowOutput()
    {
        $slow = new Unix(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Halt::new(),
            Period::second(1),
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

    #[Group('ci')]
    #[Group('local')]
    public function testTimeoutSlowOutput()
    {
        $slow = new Unix(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Halt::new(),
            Period::second(1),
            Command::foreground('php')
                ->withArgument('fixtures/slow.php')
                ->timeoutAfter(Period::second(2))
                ->withEnvironment('PATH', $_SERVER['PATH']),
        );

        $this
            ->assert()
            ->time(function() use ($slow) {
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
                $this->assertContains($output, ["0\n", "0\n1\n"]);
            })
            ->inMoreThan()
            ->seconds(2);
    }

    #[Group('ci')]
    #[Group('local')]
    public function testTimeoutWaitSlowProcess()
    {
        $slow = new Unix(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Halt::new(),
            Period::second(1),
            Command::foreground('php')
                ->withArgument('fixtures/slow.php')
                ->timeoutAfter(Period::second(2))
                ->withEnvironment('PATH', $_SERVER['PATH']),
        );
        $this
            ->assert()
            ->time(function() use ($slow) {
                $process = $slow();

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
            })
            ->inMoreThan()
            ->seconds(2);
    }

    #[Group('ci')]
    #[Group('local')]
    public function testWaitSuccess()
    {
        $cat = new Unix(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Halt::new(),
            Period::second(1),
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

    #[Group('ci')]
    #[Group('local')]
    public function testWaitFail()
    {
        $cat = new Unix(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Halt::new(),
            Period::second(1),
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

    #[Group('ci')]
    #[Group('local')]
    public function testWithInput()
    {
        $cat = new Unix(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Halt::new(),
            Period::second(1),
            Command::foreground('cat')->withInput(Content::oneShot(
                IO::fromAmbientAuthority()
                    ->streams()
                    ->acquire(\fopen('fixtures/symfony.log', 'r')),
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

    #[Group('ci')]
    #[Group('local')]
    public function testOverwrite()
    {
        @\unlink('test.log');
        $cat = new Unix(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Halt::new(),
            Period::second(1),
            Command::foreground('cat')
                ->withInput(Content::oneShot(
                    IO::fromAmbientAuthority()
                        ->streams()
                        ->acquire(\fopen('fixtures/symfony.log', 'r')),
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
