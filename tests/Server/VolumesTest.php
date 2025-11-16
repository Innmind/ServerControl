<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Volumes,
    Server\Volumes\Name,
    Server\Command,
    Exception\ProcessFailed,
};
use Innmind\TimeContinuum\Clock;
use Innmind\TimeWarp\Halt;
use Innmind\IO\IO;
use Innmind\Url\Path;
use Innmind\Immutable\SideEffect;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class VolumesTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testMountOSXVolume()
    {
        $volumes = Volumes::of(
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
        $volumes = Volumes::of(
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
        $volumes = Volumes::of(
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
        $volumes = Volumes::of(
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
        $volumes = Volumes::of(
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
        $volumes = Volumes::of(
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
        $volumes = Volumes::of(
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
        $volumes = Volumes::of(
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
        $processes = Server::new(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Halt::new(),
        )->processes();

        return Server::via(
            function($command) use ($processes, &$commands) {
                $expected = \array_shift($commands);
                $this->assertNotNull($expected);
                [$expected, $success] = $expected;
                $this->assertSame(
                    $expected,
                    $command->toString(),
                );

                return $processes->execute(Command::foreground(match ($success) {
                    true => 'echo',
                    false => 'unknown',
                }));
            },
        )->processes();
    }
}
