<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server;

use Innmind\Server\Control\Server\Command;
use Innmind\Filesystem\StreamInterface;
use Innmind\Immutable\MapInterface;
use PHPUnit\Framework\TestCase;

class CommandTest extends TestCase
{
    public function testInterface()
    {
        $command = new Command('ps');

        $this->assertFalse($command->hasWorkingDirectory());
        $this->assertFalse($command->hasInput());
        $this->assertSame('ps', (string) $command);
    }

    /**
     * @expectedException Innmind\Server\Control\Exception\EmptyExecutableNotAllowed
     */
    public function testThrowWhenEmptyForegroundExecutable()
    {
        new Command('');
    }

    public function testWithArgument()
    {
        $command = (new Command('echo'))
            ->withArgument('foo');

        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame('echo foo', (string) $command);
    }

    /**
     * @expectedException Innmind\Server\Control\Exception\EmptyArgumentNotAllowed
     */
    public function testThrowWhenEmptyArgument()
    {
        (new Command('echo'))->withArgument('');
    }

    public function testWithOption()
    {
        $command = (new Command('bin/console'))
            ->withOption('env', 'prod');

        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame('bin/console --env=prod', (string) $command);
    }

    /**
     * @expectedException Innmind\Server\Control\Exception\EmptyOptionNotAllowed
     */
    public function testThrowWhenEmptyOption()
    {
        (new Command('bin/console'))->withOption('');
    }

    public function testWithShortOption()
    {
        $command = (new Command('bin/console'))
            ->withShortOption('e', 'prod');

        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame('bin/console -e prod', (string) $command);
    }

    /**
     * @expectedException Innmind\Server\Control\Exception\EmptyOptionNotAllowed
     */
    public function testThrowWhenEmptyShortOption()
    {
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
                $input = $this->createMock(StreamInterface::class)
            );

        $this->assertInstanceOf(Command::class, $command);
        $this->assertTrue($command->hasInput());
        $this->assertSame($input, $command->input());
    }
}
