<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Volumes;

use Innmind\Server\Control\{
    Server\Volumes\Unix,
    Server\Volumes\Name,
    Server\Volumes,
    Server\Processes,
    Server\Process,
    Server\Process\Pid,
    Server\Signal,
    Server\Command,
    ScriptFailed,
};
use Innmind\TimeContinuum\Earth\Clock;
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\Stream\Streams;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Either,
    SideEffect,
};
use PHPUnit\Framework\TestCase;

class UnixTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Volumes::class,
            new Unix(
                $this->processes(),
            ),
        );
    }

    public function testMountOSXVolume()
    {
        $volumes = new Unix(
            $this->processes(
                ['which diskutil', true],
                ["diskutil 'mount' '/dev/disk1s2'", true],
            ),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $volumes->mount(
                new Name('/dev/disk1s2'),
                Path::of('/somewhere'),
            )->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    public function testThrowWhenFailToMountOSXVolume()
    {
        $volumes = new Unix(
            $this->processes(
                ['which diskutil', true],
                ["diskutil 'mount' '/dev/disk1s2'", false],
            ),
        );

        $this->assertInstanceOf(
            ScriptFailed::class,
            $volumes->mount(
                new Name('/dev/disk1s2'),
                Path::of('/somewhere'),
            )->match(
                static fn() => null,
                static fn($e) => $e,
            ),
        );
    }

    public function testUnmountOSXVolume()
    {
        $volumes = new Unix(
            $this->processes(
                ['which diskutil', true],
                ["diskutil 'unmount' '/dev/disk1s2'", true],
            ),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $volumes->unmount(new Name('/dev/disk1s2'))->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    public function testReturnErrorWhenFailToUnmountOSXVolume()
    {
        $volumes = new Unix(
            $this->processes(
                ['which diskutil', true],
                ["diskutil 'unmount' '/dev/disk1s2'", false],
            ),
        );

        $this->assertInstanceOf(
            ScriptFailed::class,
            $volumes->unmount(new Name('/dev/disk1s2'))->match(
                static fn() => null,
                static fn($e) => $e,
            ),
        );
    }

    public function testMountLinuxVolume()
    {
        $volumes = new Unix(
            $this->processes(
                ['which diskutil', false],
                ["mount '/dev/disk1s2' '/somewhere'", true],
            ),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $volumes->mount(
                new Name('/dev/disk1s2'),
                Path::of('/somewhere'),
            )->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    public function testReturnErrorWhenFailToMountLinuxVolume()
    {
        $volumes = new Unix(
            $this->processes(
                ['which diskutil', false],
                ["mount '/dev/disk1s2' '/somewhere'", false],
            ),
        );

        $this->assertInstanceOf(
            ScriptFailed::class,
            $volumes->mount(
                new Name('/dev/disk1s2'),
                Path::of('/somewhere'),
            )->match(
                static fn() => null,
                static fn($e) => $e,
            ),
        );
    }

    public function testUnmountLinuxVolume()
    {
        $volumes = new Unix(
            $this->processes(
                ['which diskutil', false],
                ["umount '/dev/disk1s2'", true],
            ),
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $volumes->unmount(new Name('/dev/disk1s2'))->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    public function testReturnErrorWhenFailToUnmountLinuxVolume()
    {
        $volumes = new Unix(
            $this->processes(
                ['which diskutil', false],
                ["umount '/dev/disk1s2'", false],
            ),
        );

        $this->assertInstanceOf(
            ScriptFailed::class,
            $volumes->unmount(new Name('/dev/disk1s2'))->match(
                static fn() => null,
                static fn($e) => $e,
            ),
        );
    }

    private function processes(array ...$commands): Processes
    {
        $processes = Processes\Unix::of(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
        );

        return new class($processes, $this, $commands) implements Processes {
            public function __construct(
                private $processes,
                private $test,
                private $commands,
            ) {
            }

            public function execute(Command $command): Process
            {
                $expected = \array_shift($this->commands);
                $this->test->assertNotNull($expected);
                [$expected, $success] = $expected;
                $this->test->assertSame(
                    $expected,
                    $command->toString(),
                );

                return $this->processes->execute(Command::foreground(match ($success) {
                    true => 'echo',
                    false => 'unknown',
                }));
            }

            public function kill(Pid $pid, Signal $signal): Either
            {
            }
        };
    }
}
