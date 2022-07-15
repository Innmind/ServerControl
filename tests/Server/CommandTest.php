<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server\Command,
    Server\Second,
};
use Innmind\Filesystem\File\Content;
use Innmind\Url\Path;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class CommandTest extends TestCase
{
    public function testInterface()
    {
        $command = Command::foreground('ps');

        $this->assertFalse($command->workingDirectory()->match(
            static fn() => true,
            static fn() => false,
        ));
        $this->assertFalse($command->input()->match(
            static fn() => true,
            static fn() => false,
        ));
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
            Command::foreground('echo')->withArgument('')->toString(),
        );
    }

    public function testWithOption()
    {
        $command = Command::foreground('bin/console')
            ->withOption('env', 'prod');

        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame("bin/console '--env=prod'", $command->toString());
    }

    public function testWithShortOption()
    {
        $command = Command::foreground('bin/console')
            ->withShortOption('e', 'prod');

        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame("bin/console '-e' 'prod'", $command->toString());
    }

    public function testWithEnvironment()
    {
        $command = Command::foreground('bin/console')
            ->withEnvironment('SYMFONY_ENV', 'prod');

        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame('bin/console', $command->toString());
        $this->assertInstanceOf(Map::class, $command->environment());
        $this->assertCount(1, $command->environment());
        $this->assertSame('prod', $command->environment()->get('SYMFONY_ENV')->match(
            static fn($env) => $env,
            static fn() => null,
        ));
    }

    public function testWithEnvironments()
    {
        $command = Command::foreground('bin/console')
            ->withEnvironment('SYMFONY_ENV', 'prod')
            ->withEnvironments(Map::of(['HOME', '/home/foo'], ['USER', 'foo']));

        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame('bin/console', $command->toString());
        $this->assertInstanceOf(Map::class, $command->environment());
        $this->assertCount(3, $command->environment());
        $this->assertSame('prod', $command->environment()->get('SYMFONY_ENV')->match(
            static fn($env) => $env,
            static fn() => null,
        ));
        $this->assertSame('/home/foo', $command->environment()->get('HOME')->match(
            static fn($env) => $env,
            static fn() => null,
        ));
        $this->assertSame('foo', $command->environment()->get('USER')->match(
            static fn($env) => $env,
            static fn() => null,
        ));
    }

    public function testWithWorkingDirectory()
    {
        $command = Command::foreground('bin/console')
            ->withWorkingDirectory(Path::of('/var/www/app'));

        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame('bin/console', $command->toString());
        $this->assertSame('/var/www/app', $command->workingDirectory()->match(
            static fn($path) => $path->toString(),
            static fn() => null,
        ));
    }

    public function testWithInput()
    {
        $command = Command::foreground('bin/console')
            ->withInput(
                $input = $this->createMock(Content::class),
            );

        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame($input, $command->input()->match(
            static fn($input) => $input,
            static fn() => null,
        ));
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
            $command->toString(),
        );
    }

    public function testTimeout()
    {
        $commandA = Command::foreground('echo');
        $commandB = $commandA->timeoutAfter($timeout = new Second(1));

        $this->assertFalse($commandA->timeout()->match(
            static fn() => true,
            static fn() => false,
        ));
        $this->assertSame($timeout, $commandB->timeout()->match(
            static fn($timeout) => $timeout,
            static fn() => null,
        ));
    }
}
