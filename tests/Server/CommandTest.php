<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server;

use Innmind\Server\Control\Server\Command;
use Innmind\Immutable\MapInterface;
use PHPUnit\Framework\TestCase;

class CommandTest extends TestCase
{
    public function testForeground()
    {
        $command = Command::foreground('ps');

        $this->assertInstanceOf(Command::class, $command);
        $this->assertFalse($command->toBeRunInBackground());
        $this->assertFalse($command->hasWorkingDirectory());
        $this->assertSame('ps', (string) $command);
    }

    public function testBackground()
    {
        $command = Command::background('ps');

        $this->assertInstanceOf(Command::class, $command);
        $this->assertTrue($command->toBeRunInBackground());
        $this->assertFalse($command->hasWorkingDirectory());
        $this->assertSame('ps', (string) $command);
    }

    /**
     * @expectedException Innmind\Server\Control\Exception\EmptyExecutableNotAllowed
     */
    public function testThrowWhenEmptyForegroundExecutable()
    {
        Command::foreground('');
    }

    /**
     * @expectedException Innmind\Server\Control\Exception\EmptyExecutableNotAllowed
     */
    public function testThrowWhenEmptyBackgroundExecutable()
    {
        Command::background('');
    }

    public function testWithArgument()
    {
        $command = Command::foreground('echo')
            ->withArgument('foo');

        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame('echo foo', (string) $command);
    }

    /**
     * @expectedException Innmind\Server\Control\Exception\EmptyArgumentNotAllowed
     */
    public function testThrowWhenEmptyArgument()
    {
        Command::foreground('echo')->withArgument('');
    }

    public function testWithOption()
    {
        $command = Command::foreground('bin/console')
            ->withOption('env', 'prod');

        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame('bin/console --env=prod', (string) $command);
    }

    /**
     * @expectedException Innmind\Server\Control\Exception\EmptyOptionNotAllowed
     */
    public function testThrowWhenEmptyOption()
    {
        Command::foreground('bin/console')->withOption('');
    }

    public function testWithShortOption()
    {
        $command = Command::foreground('bin/console')
            ->withShortOption('e', 'prod');

        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame('bin/console -e prod', (string) $command);
    }

    /**
     * @expectedException Innmind\Server\Control\Exception\EmptyOptionNotAllowed
     */
    public function testThrowWhenEmptyShortOption()
    {
        Command::foreground('bin/console')->withShortOption('');
    }

    public function testWithEnvironment()
    {
        $command = Command::foreground('bin/console')
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
        $command = Command::foreground('bin/console')
            ->withWorkingDirectory('/var/www/app');

        $this->assertInstanceOf(Command::class, $command);
        $this->assertTrue($command->hasWorkingDirectory());
        $this->assertSame('bin/console', (string) $command);
        $this->assertSame('/var/www/app', $command->workingDirectory());
    }
}
