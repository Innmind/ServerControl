<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Server\{
    Process\Unix,
    Process\Output\Type,
    Command,
};
use Innmind\Url\Path;
use Innmind\Stream\Readable\Stream;
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
        $cat = new Unix(Command::foreground('echo')->withArgument('hello'));
        $count = 0;
        $process = $cat();

        foreach ($process->output() as $type => $value) {
            $this->assertSame(Type::output, $type);
            $this->assertSame("hello\n", $value->toString());
            ++$count;
        }

        $this->assertSame(1, $count);
        $this->assertGreaterThanOrEqual(2, $process->pid()->toInt());
    }

    public function testOutput()
    {
        $this
            ->forAll(Set\Decorate::immutable(
                static fn($chars) => \implode('', $chars),
                Set\Sequence::of(
                    Set\Chars::ascii()->filter(static fn($char) => $char !== '\\'),
                    Set\Integers::between(1, 126),
                ),
            ))
            ->then(function($echo) {
                $cat = new Unix(Command::foreground('echo')->withArgument($echo));
                $process = $cat();
                $output = '';

                foreach ($process->output() as $type => $value) {
                    $output .= $value->toString();
                }

                $this->assertSame("$echo\n", $output);
                $this->assertGreaterThanOrEqual(2, $process->pid()->toInt());
            });
    }

    public function testSlowOutput()
    {
        $slow = new Unix(Command::foreground('php')->withArgument('fixtures/slow.php'));
        $process = $slow();
        $count = 0;
        $output = '';

        $this->assertGreaterThanOrEqual(2, $process->pid()->toInt());

        foreach ($process->output() as $type => $chunk) {
            $output .= $chunk->toString();
            $this->assertSame($count % 2 === 0 ? Type::output : Type::error, $type);
            ++$count;
        }

        $this->assertSame("0\n1\n2\n3\n4\n5\n", $output);
    }

    public function testWaitSuccess()
    {
        $cat = new Unix(Command::foreground('echo')->withArgument('hello'));

        $this->assertSame(0, $cat()->wait()->toInt());
    }

    public function testWaitFail()
    {
        $cat = new Unix(Command::foreground('php')->withArgument('fixtures/fails.php'));

        $this->assertSame(1, $cat()->wait()->toInt());
    }
}
