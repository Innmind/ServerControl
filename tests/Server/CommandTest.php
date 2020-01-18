<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server\Command,
    Exception\EmptyExecutableNotAllowed,
    Exception\EmptyOptionNotAllowed,
};
use Innmind\Stream\Readable;
use Innmind\Immutable\MapInterface;
use PHPUnit\Framework\TestCase;

class CommandTest extends TestCase
{
    public function testInterface()
    {
        $command = new Command('ps');

        $this->assertFalse($command->hasWorkingDirectory());
        $this->assertFalse($command->hasInput());
        $this->assertFalse($command->toBeRunInBackground());
        $this->assertSame('ps', (string) $command);
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

        new Command('');
    }

    public function testWithArgument()
    {
        $command = (new Command('echo'))
            ->withArgument('foo');

        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame("echo 'foo'", (string) $command);
    }

    public function testDoesntThrowWhenEmptyArgument()
    {
        $this->assertSame(
            "echo ''",
            (string) (new Command('echo'))->withArgument('')
        );
    }

    public function testWithOption()
    {
        $command = (new Command('bin/console'))
            ->withOption('env', 'prod');

        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame("bin/console '--env=prod'", (string) $command);
    }

    public function testThrowWhenEmptyOption()
    {
        $this->expectException(EmptyOptionNotAllowed::class);

        (new Command('bin/console'))->withOption('');
    }

    public function testWithShortOption()
    {
        $command = (new Command('bin/console'))
            ->withShortOption('e', 'prod');

        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame("bin/console '-e' 'prod'", (string) $command);
    }

    public function testThrowWhenEmptyShortOption()
    {
        $this->expectException(EmptyOptionNotAllowed::class);

        (new Command('bin/console'))->withShortOption('');
    }

    public function testWithEnvironment()
    {
        $command = (new Command('bin/console'))
            ->withEnvironment('SYMFONY_ENV', 'prod');

        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame('bin/console', (string) $command);
        $this->assertInstanceOf(MapInterface::class, $command->environment());
        $this->assertSame('string', (string) $command->environment()->keyType());
        $this->assertSame('string', (string) $command->environment()->valueType());
        $this->assertCount(1, $command->environment());
        $this->assertSame('prod', $command->environment()->get('SYMFONY_ENV'));
    }

    public function testWithWorkingDirectory()
    {
        $command = (new Command('bin/console'))
            ->withWorkingDirectory('/var/www/app');

        $this->assertInstanceOf(Command::class, $command);
        $this->assertTrue($command->hasWorkingDirectory());
        $this->assertSame('bin/console', (string) $command);
        $this->assertSame('/var/www/app', $command->workingDirectory());
    }

    public function testWithInput()
    {
        $command = (new Command('bin/console'))
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
            ->overwrite('foo.txt');

        $this->assertSame("echo 'bar' > 'foo.txt'", (string) $command);
    }

    public function testDoesntOverwriteWhenEmptyPath()
    {
        $command = Command::foreground('echo')
            ->withArgument('bar')
            ->overwrite('');

        $this->assertSame("echo 'bar'", (string) $command);
    }

    public function testAppend()
    {
        $command = Command::foreground('echo')
            ->withArgument('bar')
            ->append('foo.txt');

        $this->assertSame("echo 'bar' >> 'foo.txt'", (string) $command);
    }

    public function testDoesntAppendWhenEmptyPath()
    {
        $command = Command::foreground('echo')
            ->withArgument('bar')
            ->append('');

        $this->assertSame("echo 'bar'", (string) $command);
    }

    public function testPipe()
    {
        $commandA = Command::foreground('echo')
            ->withArgument('bar')
            ->append('foo.txt');
        $commandB = Command::foreground('cat')
            ->withArgument('foo.txt');
        $commandC = Command::foreground('wc')
            ->overwrite('count.txt');

        $command = $commandA->pipe($commandB)->pipe($commandC);

        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame("echo 'bar' >> 'foo.txt'", (string) $commandA);
        $this->assertSame("cat 'foo.txt'", (string) $commandB);
        $this->assertSame("wc > 'count.txt'", (string) $commandC);
        $this->assertSame(
            "echo 'bar' >> 'foo.txt' | 'cat' 'foo.txt' | 'wc' > 'count.txt'",
            (string) $command
        );
    }
}
