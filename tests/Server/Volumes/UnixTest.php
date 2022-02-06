<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Volumes;

use Innmind\Server\Control\{
    Server\Volumes\Unix,
    Server\Volumes\Name,
    Server\Volumes,
    Server\Processes,
    Server\Process,
    Server\Process\ExitCode,
    ProcessFailed,
    ScriptFailed,
};
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
                $this->createMock(Processes::class),
            ),
        );
    }

    public function testMountOSXVolume()
    {
        $volumes = new Unix(
            $processes = $this->createMock(Processes::class),
        );
        $which = $this->createMock(Process::class);
        $which
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $mount = $this->createMock(Process::class);
        $mount
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $processes
            ->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [$this->callback(static function($command): bool {
                    return $command->toString() === 'which diskutil';
                })],
                [$this->callback(static function($command): bool {
                    return $command->toString() === "diskutil 'mount' '/dev/disk1s2'";
                })],
            )
            ->will($this->onConsecutiveCalls($which, $mount));

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
            $processes = $this->createMock(Processes::class),
        );
        $which = $this->createMock(Process::class);
        $which
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $mount = $this->createMock(Process::class);
        $mount
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::left(new ProcessFailed(new ExitCode(1))));
        $processes
            ->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [$this->callback(static function($command): bool {
                    return $command->toString() === 'which diskutil';
                })],
                [$this->callback(static function($command): bool {
                    return $command->toString() === "diskutil 'mount' '/dev/disk1s2'";
                })],
            )
            ->will($this->onConsecutiveCalls($which, $mount));

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
            $processes = $this->createMock(Processes::class),
        );
        $which = $this->createMock(Process::class);
        $which
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $mount = $this->createMock(Process::class);
        $mount
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $processes
            ->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [$this->callback(static function($command): bool {
                    return $command->toString() === 'which diskutil';
                })],
                [$this->callback(static function($command): bool {
                    return $command->toString() === "diskutil 'unmount' '/dev/disk1s2'";
                })],
            )
            ->will($this->onConsecutiveCalls($which, $mount));

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
            $processes = $this->createMock(Processes::class),
        );
        $which = $this->createMock(Process::class);
        $which
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $mount = $this->createMock(Process::class);
        $mount
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::left(new ProcessFailed(new ExitCode(1))));
        $processes
            ->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [$this->callback(static function($command): bool {
                    return $command->toString() === 'which diskutil';
                })],
                [$this->callback(static function($command): bool {
                    return $command->toString() === "diskutil 'unmount' '/dev/disk1s2'";
                })],
            )
            ->will($this->onConsecutiveCalls($which, $mount));

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
            $processes = $this->createMock(Processes::class),
        );
        $which = $this->createMock(Process::class);
        $which
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::left(new ProcessFailed(new ExitCode(1))));
        $mount = $this->createMock(Process::class);
        $mount
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $processes
            ->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [$this->callback(static function($command): bool {
                    return $command->toString() === 'which diskutil';
                })],
                [$this->callback(static function($command): bool {
                    return $command->toString() === "mount '/dev/disk1s2' '/somewhere'";
                })],
            )
            ->will($this->onConsecutiveCalls($which, $mount));

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
            $processes = $this->createMock(Processes::class),
        );
        $which = $this->createMock(Process::class);
        $which
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::left(new ProcessFailed(new ExitCode(1))));
        $mount = $this->createMock(Process::class);
        $mount
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::left(new ProcessFailed(new ExitCode(1))));
        $processes
            ->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [$this->callback(static function($command): bool {
                    return $command->toString() === 'which diskutil';
                })],
                [$this->callback(static function($command): bool {
                    return $command->toString() === "mount '/dev/disk1s2' '/somewhere'";
                })],
            )
            ->will($this->onConsecutiveCalls($which, $mount));

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
            $processes = $this->createMock(Processes::class),
        );
        $which = $this->createMock(Process::class);
        $which
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::left(new ProcessFailed(new ExitCode(1))));
        $mount = $this->createMock(Process::class);
        $mount
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $processes
            ->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [$this->callback(static function($command): bool {
                    return $command->toString() === 'which diskutil';
                })],
                [$this->callback(static function($command): bool {
                    return $command->toString() === "umount '/dev/disk1s2'";
                })],
            )
            ->will($this->onConsecutiveCalls($which, $mount));

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
            $processes = $this->createMock(Processes::class),
        );
        $which = $this->createMock(Process::class);
        $which
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::left(new ProcessFailed(new ExitCode(1))));
        $mount = $this->createMock(Process::class);
        $mount
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::left(new ProcessFailed(new ExitCode(1))));
        $processes
            ->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive(
                [$this->callback(static function($command): bool {
                    return $command->toString() === 'which diskutil';
                })],
                [$this->callback(static function($command): bool {
                    return $command->toString() === "umount '/dev/disk1s2'";
                })],
            )
            ->will($this->onConsecutiveCalls($which, $mount));

        $this->assertInstanceOf(
            ScriptFailed::class,
            $volumes->unmount(new Name('/dev/disk1s2'))->match(
                static fn() => null,
                static fn($e) => $e,
            ),
        );
    }
}
