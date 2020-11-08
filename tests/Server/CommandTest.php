<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server\Command,
    Server\Second,
    Exception\EmptyExecutableNotAllowed,
    Exception\EmptyOptionNotAllowed,
};
use Innmind\Stream\Readable;
use Innmind\Url\Path;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class CommandTest extends TestCase
{
    public function testInterface()
    {
        $command = Command::foreground('ps');

        $this->assertFalse($command->hasWorkingDirectory());
        $this->assertFalse($command->hasInput());
        $this->assertFalse($command->toBeRunInBackground());
        $this->assertSame('ps', $command->toString());
    }

    public function testBackground()
    {
        $command = Command::background('ps');

        $this->assertTrue($command->toBeRunInBackground());
    }

    public function testForeground()
    {
        $command = Command::foreground('ps');

        $this->assertFalse($command->toBeRunInBackground());
    }

    public function testThrowWhenEmptyForegroundExecutable()
    {
        $this->expectException(EmptyExecutableNotAllowed::class);

        Command::foreground('');
    }

    public function testWithArgument()
    {
        $command = Command::foreground('echo')
            ->withArgument('foo');

        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame("echo 'foo'", $command->toString());
    }

    public function testDoesntThrowWhenEmptyArgument()
    {
        $this->assertSame(
            "echo ''",
            Command::foreground('echo')->withArgument('')->toString()
        );
    }

    public function testWithOption()
    {
        $command = Command::foreground('bin/console')
            ->withOption('env', 'prod');

        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame("bin/console '--env=prod'", $command->toString());
    }

    public function testThrowWhenEmptyOption()
    {
        $this->expectException(EmptyOptionNotAllowed::class);

        Command::foreground('bin/console')->withOption('');
    }

    public function testWithShortOption()
    {
        $command = Command::foreground('bin/console')
            ->withShortOption('e', 'prod');

        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame("bin/console '-e' 'prod'", $command->toString());
    }

    public function testThrowWhenEmptyShortOption()
    {
        $this->expectException(EmptyOptionNotAllowed::class);

        Command::foreground('bin/console')->withShortOption('');
    }

    public function testWithEnvironment()
    {
        $command = Command::foreground('bin/console')
            ->withEnvironment('SYMFONY_ENV', 'prod');

        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame('bin/console', $command->toString());
        $this->assertInstanceOf(Map::class, $command->environment());
        $this->assertSame('string', (string) $command->environment()->keyType());
        $this->assertSame('string', (string) $command->environment()->valueType());
        $this->assertCount(1, $command->environment());
        $this->assertSame('prod', $command->environment()->get('SYMFONY_ENV'));
    }

    public function testWithWorkingDirectory()
    {
        $command = Command::foreground('bin/console')
            ->withWorkingDirectory(Path::of('/var/www/app'));

        $this->assertInstanceOf(Command::class, $command);
        $this->assertTrue($command->hasWorkingDirectory());
        $this->assertSame('bin/console', $command->toString());
        $this->assertSame('/var/www/app', $command->workingDirectory()->toString());
    }

    public function testWithInput()
    {
        $command = Command::foreground('bin/console')
            ->withInput(
                $input = $this->createMock(Readable::class)
            );

        $this->assertInstanceOf(Command::class, $command);
        $this->assertTrue($command->hasInput());
        $this->assertSame($input, $command->input());
    }

    public function testOverwrite()
    {
        $command = Command::foreground('echo')
            ->withArgument('bar')
            ->overwrite(Path::of('foo.txt'));

        $this->assertSame("echo 'bar' > 'foo.txt'", $command->toString());
    }

    public function testAppend()
    {
        $command = Command::foreground('echo')
            ->withArgument('bar')
            ->append(Path::of('foo.txt'));

        $this->assertSame("echo 'bar' >> 'foo.txt'", $command->toString());
    }

    public function testPipe()
    {
        $commandA = Command::foreground('echo')
            ->withArgument('bar')
            ->append(Path::of('foo.txt'));
        $commandB = Command::foreground('cat')
            ->withArgument('foo.txt');
        $commandC = Command::foreground('wc')
            ->overwrite(Path::of('count.txt'));

        $command = $commandA->pipe($commandB)->pipe($commandC);

        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame("echo 'bar' >> 'foo.txt'", $commandA->toString());
        $this->assertSame("cat 'foo.txt'", $commandB->toString());
        $this->assertSame("wc > 'count.txt'", $commandC->toString());
        $this->assertSame(
            "echo 'bar' >> 'foo.txt' | 'cat' 'foo.txt' | 'wc' > 'count.txt'",
            $command->toString()
        );
    }

    public function testTimeout()
    {
        $commandA = Command::foreground('echo');
        $commandB = $commandA->timeoutAfter($timeout = new Second(1));

        $this->assertFalse($commandA->shouldTimeout());
        $this->assertTrue($commandB->shouldTimeout());
        $this->assertSame($timeout, $commandB->timeout());
    }
}
