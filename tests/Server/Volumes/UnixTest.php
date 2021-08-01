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
    Exception\ScriptFailed,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Either,
    Maybe,
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
            ->willReturn(Either::right(Maybe::just(new ExitCode(0))));
        $mount = $this->createMock(Process::class);
        $mount
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(Maybe::just(new ExitCode(0))));
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

        $this->assertNull($volumes->mount(
            new Name('/dev/disk1s2'),
            Path::of('/somewhere'),
        ));
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
            ->willReturn(Either::right(Maybe::just(new ExitCode(0))));
        $mount = $this->createMock(Process::class);
        $mount
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(Maybe::just(new ExitCode(1))));
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

        $this->expectException(ScriptFailed::class);

        $volumes->mount(
            new Name('/dev/disk1s2'),
            Path::of('/somewhere'),
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
            ->willReturn(Either::right(Maybe::just(new ExitCode(0))));
        $mount = $this->createMock(Process::class);
        $mount
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(Maybe::just(new ExitCode(0))));
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

        $this->assertNull($volumes->unmount(
            new Name('/dev/disk1s2'),
        ));
    }

    public function testThrowWhenFailToUnmountOSXVolume()
    {
        $volumes = new Unix(
            $processes = $this->createMock(Processes::class),
        );
        $which = $this->createMock(Process::class);
        $which
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(Maybe::just(new ExitCode(0))));
        $mount = $this->createMock(Process::class);
        $mount
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(Maybe::just(new ExitCode(1))));
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

        $this->expectException(ScriptFailed::class);

        $volumes->unmount(
            new Name('/dev/disk1s2'),
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
            ->willReturn(Either::right(Maybe::just(new ExitCode(1))));
        $mount = $this->createMock(Process::class);
        $mount
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(Maybe::just(new ExitCode(0))));
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

        $this->assertNull($volumes->mount(
            new Name('/dev/disk1s2'),
            Path::of('/somewhere'),
        ));
    }

    public function testThrowWhenFailToMountLinuxVolume()
    {
        $volumes = new Unix(
            $processes = $this->createMock(Processes::class),
        );
        $which = $this->createMock(Process::class);
        $which
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(Maybe::just(new ExitCode(1))));
        $mount = $this->createMock(Process::class);
        $mount
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(Maybe::just(new ExitCode(1))));
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

        $this->expectException(ScriptFailed::class);

        $volumes->mount(
            new Name('/dev/disk1s2'),
            Path::of('/somewhere'),
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
            ->willReturn(Either::right(Maybe::just(new ExitCode(1))));
        $mount = $this->createMock(Process::class);
        $mount
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(Maybe::just(new ExitCode(0))));
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

        $this->assertNull($volumes->unmount(
            new Name('/dev/disk1s2'),
        ));
    }

    public function testThrowWhenFailToUnmountLinuxVolume()
    {
        $volumes = new Unix(
            $processes = $this->createMock(Processes::class),
        );
        $which = $this->createMock(Process::class);
        $which
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(Maybe::just(new ExitCode(1))));
        $mount = $this->createMock(Process::class);
        $mount
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(Maybe::just(new ExitCode(1))));
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

        $this->expectException(ScriptFailed::class);

        $volumes->unmount(
            new Name('/dev/disk1s2'),
        );
    }
}
