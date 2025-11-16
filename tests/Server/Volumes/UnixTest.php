<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Volumes;

use Innmind\Server\Control\{
    Server\Volumes\Unix,
    Server\Volumes\Name,
    Server\Volumes,
    Server\Processes,
    Server\Process\Pid,
    Server\Signal,
    Server\Command,
    Exception\ProcessFailed,
};
use Innmind\TimeContinuum\Clock;
use Innmind\TimeWarp\Halt;
use Innmind\IO\IO;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class UnixTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $this->assertInstanceOf(
            Volumes::class,
            new Unix(
                $this->processes(),
            ),
        );
    }

    #[Group('ci')]
    #[Group('local')]
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
                Name::of('/dev/disk1s2'),
                Path::of('/somewhere'),
            )->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    public function testThrowWhenFailToMountOSXVolume()
    {
        $volumes = new Unix(
            $this->processes(
                ['which diskutil', true],
                ["diskutil 'mount' '/dev/disk1s2'", false],
            ),
        );

        $this->assertInstanceOf(
            ProcessFailed::class,
            $volumes->mount(
                Name::of('/dev/disk1s2'),
                Path::of('/somewhere'),
            )->match(
                static fn() => null,
                static fn($e) => $e,
            ),
        );
    }

    #[Group('ci')]
    #[Group('local')]
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
            $volumes->unmount(Name::of('/dev/disk1s2'))->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    public function testReturnErrorWhenFailToUnmountOSXVolume()
    {
        $volumes = new Unix(
            $this->processes(
                ['which diskutil', true],
                ["diskutil 'unmount' '/dev/disk1s2'", false],
            ),
        );

        $this->assertInstanceOf(
            ProcessFailed::class,
            $volumes->unmount(Name::of('/dev/disk1s2'))->match(
                static fn() => null,
                static fn($e) => $e,
            ),
        );
    }

    #[Group('ci')]
    #[Group('local')]
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
                Name::of('/dev/disk1s2'),
                Path::of('/somewhere'),
            )->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    public function testReturnErrorWhenFailToMountLinuxVolume()
    {
        $volumes = new Unix(
            $this->processes(
                ['which diskutil', false],
                ["mount '/dev/disk1s2' '/somewhere'", false],
            ),
        );

        $this->assertInstanceOf(
            ProcessFailed::class,
            $volumes->mount(
                Name::of('/dev/disk1s2'),
                Path::of('/somewhere'),
            )->match(
                static fn() => null,
                static fn($e) => $e,
            ),
        );
    }

    #[Group('ci')]
    #[Group('local')]
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
            $volumes->unmount(Name::of('/dev/disk1s2'))->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    public function testReturnErrorWhenFailToUnmountLinuxVolume()
    {
        $volumes = new Unix(
            $this->processes(
                ['which diskutil', false],
                ["umount '/dev/disk1s2'", false],
            ),
        );

        $this->assertInstanceOf(
            ProcessFailed::class,
            $volumes->unmount(Name::of('/dev/disk1s2'))->match(
                static fn() => null,
                static fn($e) => $e,
            ),
        );
    }

    private function processes(array ...$commands): Processes
    {
        $processes = Processes\Unix::of(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Halt::new(),
        );

        return new class($processes, $this, $commands) implements Processes {
            public function __construct(
                private $processes,
                private $test,
                private $commands,
            ) {
            }

            public function execute(Command $command): Attempt
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

            public function kill(Pid $pid, Signal $signal): Attempt
            {
            }
        };
    }
}
