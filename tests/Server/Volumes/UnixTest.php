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
    ScriptFailed,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Either,
    SideEffect,
    Sequence,
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
            ->expects($matcher = $this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function($command) use ($matcher, $which, $mount) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertSame(
                        'which diskutil',
                        $command->toString(),
                    ),
                    2 => $this->assertSame(
                        "diskutil 'mount' '/dev/disk1s2'",
                        $command->toString(),
                    ),
                };

                return match ($matcher->numberOfInvocations()) {
                    1 => $which,
                    2 => $mount,
                };
            });

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
            ->willReturn(Either::left(new Process\Failed(
                new ExitCode(1),
                Sequence::of(),
            )));
        $processes
            ->expects($matcher = $this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function($command) use ($matcher, $which, $mount) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertSame(
                        'which diskutil',
                        $command->toString(),
                    ),
                    2 => $this->assertSame(
                        "diskutil 'mount' '/dev/disk1s2'",
                        $command->toString(),
                    ),
                };

                return match ($matcher->numberOfInvocations()) {
                    1 => $which,
                    2 => $mount,
                };
            });

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
            ->expects($matcher = $this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function($command) use ($matcher, $which, $mount) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertSame(
                        'which diskutil',
                        $command->toString(),
                    ),
                    2 => $this->assertSame(
                        "diskutil 'unmount' '/dev/disk1s2'",
                        $command->toString(),
                    ),
                };

                return match ($matcher->numberOfInvocations()) {
                    1 => $which,
                    2 => $mount,
                };
            });

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
            ->willReturn(Either::left(new Process\Failed(
                new ExitCode(1),
                Sequence::of(),
            )));
        $processes
            ->expects($matcher = $this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function($command) use ($matcher, $which, $mount) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertSame(
                        'which diskutil',
                        $command->toString(),
                    ),
                    2 => $this->assertSame(
                        "diskutil 'unmount' '/dev/disk1s2'",
                        $command->toString(),
                    ),
                };

                return match ($matcher->numberOfInvocations()) {
                    1 => $which,
                    2 => $mount,
                };
            });

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
            ->willReturn(Either::left(new Process\Failed(
                new ExitCode(1),
                Sequence::of(),
            )));
        $mount = $this->createMock(Process::class);
        $mount
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $processes
            ->expects($matcher = $this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function($command) use ($matcher, $which, $mount) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertSame(
                        'which diskutil',
                        $command->toString(),
                    ),
                    2 => $this->assertSame(
                        "mount '/dev/disk1s2' '/somewhere'",
                        $command->toString(),
                    ),
                };

                return match ($matcher->numberOfInvocations()) {
                    1 => $which,
                    2 => $mount,
                };
            });

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
            ->willReturn(Either::left(new Process\Failed(
                new ExitCode(1),
                Sequence::of(),
            )));
        $mount = $this->createMock(Process::class);
        $mount
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::left(new Process\Failed(
                new ExitCode(1),
                Sequence::of(),
            )));
        $processes
            ->expects($matcher = $this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function($command) use ($matcher, $which, $mount) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertSame(
                        'which diskutil',
                        $command->toString(),
                    ),
                    2 => $this->assertSame(
                        "mount '/dev/disk1s2' '/somewhere'",
                        $command->toString(),
                    ),
                };

                return match ($matcher->numberOfInvocations()) {
                    1 => $which,
                    2 => $mount,
                };
            });

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
            ->willReturn(Either::left(new Process\Failed(
                new ExitCode(1),
                Sequence::of(),
            )));
        $mount = $this->createMock(Process::class);
        $mount
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::right(new SideEffect));
        $processes
            ->expects($matcher = $this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function($command) use ($matcher, $which, $mount) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertSame(
                        'which diskutil',
                        $command->toString(),
                    ),
                    2 => $this->assertSame(
                        "umount '/dev/disk1s2'",
                        $command->toString(),
                    ),
                };

                return match ($matcher->numberOfInvocations()) {
                    1 => $which,
                    2 => $mount,
                };
            });

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
            ->willReturn(Either::left(new Process\Failed(
                new ExitCode(1),
                Sequence::of(),
            )));
        $mount = $this->createMock(Process::class);
        $mount
            ->expects($this->once())
            ->method('wait')
            ->willReturn(Either::left(new Process\Failed(
                new ExitCode(1),
                Sequence::of(),
            )));
        $processes
            ->expects($matcher = $this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function($command) use ($matcher, $which, $mount) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertSame(
                        'which diskutil',
                        $command->toString(),
                    ),
                    2 => $this->assertSame(
                        "umount '/dev/disk1s2'",
                        $command->toString(),
                    ),
                };

                return match ($matcher->numberOfInvocations()) {
                    1 => $which,
                    2 => $mount,
                };
            });

        $this->assertInstanceOf(
            ScriptFailed::class,
            $volumes->unmount(new Name('/dev/disk1s2'))->match(
                static fn() => null,
                static fn($e) => $e,
            ),
        );
    }
}
