<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Servers\Mock,
    Server,
    Server\Volumes,
    Server\Command,
    Server\Process\Success,
    Server\Process\Signaled,
    Server\Process\TimedOut,
    Server\Process\Failed,
    Server\Process\Pid,
    Server\Signal,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    SideEffect,
};
use Innmind\BlackBox\{
    PHPUnit\Framework\TestCase,
    Runner\Assert\Failure,
};
use PHPUnit\Framework\Attributes\Group;

class MockTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $this->assertInstanceOf(
            Server::class,
            Mock::new($this->assert()),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testWillReboot()
    {
        $mock = Mock::new($this->assert())
            ->willReboot();

        $this->assertInstanceOf(
            SideEffect::class,
            $mock
                ->reboot()
                ->unwrap(),
        );
        $this
            ->assert()
            ->not()
            ->throws(static fn() => $mock->assert());
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testWillFailToReboot()
    {
        $mock = Mock::new($this->assert())
            ->willFailToReboot();

        $this->assertFalse(
            $mock
                ->reboot()
                ->match(
                    static fn() => true,
                    static fn() => false,
                ),
        );
        $this
            ->assert()
            ->not()
            ->throws(static fn() => $mock->assert());
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testUnexpectedReboot()
    {
        $mock = Mock::new($this->assert());

        try {
            $mock->reboot();
        } catch (\Throwable $e) {
            $this->assertInstanceOf(Failure::class, $e);

            return;
        }

        $this->fail('It should throw');
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testUncalledReboot()
    {
        $mock = Mock::new($this->assert())
            ->willReboot();

        try {
            $mock->assert();
        } catch (\Throwable $e) {
            $this->assertInstanceOf(Failure::class, $e);

            return;
        }

        $this->fail('It should throw');
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testWillShutdown()
    {
        $mock = Mock::new($this->assert())
            ->willShutdown();

        $this->assertInstanceOf(
            SideEffect::class,
            $mock
                ->shutdown()
                ->unwrap(),
        );
        $this
            ->assert()
            ->not()
            ->throws(static fn() => $mock->assert());
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testWillFailToShutdown()
    {
        $mock = Mock::new($this->assert())
            ->willFailToShutdown();

        $this->assertFalse(
            $mock
                ->shutdown()
                ->match(
                    static fn() => true,
                    static fn() => false,
                ),
        );
        $this
            ->assert()
            ->not()
            ->throws(static fn() => $mock->assert());
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testUnexpectedShutdown()
    {
        $mock = Mock::new($this->assert());

        try {
            $mock->shutdown();
        } catch (\Throwable $e) {
            $this->assertInstanceOf(Failure::class, $e);

            return;
        }

        $this->fail('It should throw');
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testUncalledShutdown()
    {
        $mock = Mock::new($this->assert())
            ->willShutdown();

        try {
            $mock->assert();
        } catch (\Throwable $e) {
            $this->assertInstanceOf(Failure::class, $e);

            return;
        }

        $this->fail('It should throw');
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testWillMountVolume()
    {
        $mock = Mock::new($this->assert())
            ->willMountVolume('foo', '/bar');

        $this->assertInstanceOf(
            SideEffect::class,
            $mock
                ->volumes()
                ->mount(Volumes\Name::of('foo'), Path::of('/bar'))
                ->unwrap(),
        );
        $this
            ->assert()
            ->not()
            ->throws(static fn() => $mock->assert());
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testWillMountVolumeWithWrongName()
    {
        $mock = Mock::new($this->assert())
            ->willMountVolume('foo', '/bar');

        try {
            $mock
                ->volumes()
                ->mount(Volumes\Name::of('bar'), Path::of('/bar'));
        } catch (\Throwable $e) {
            $this->assertInstanceOf(Failure::class, $e);

            return;
        }

        $this->fail('It should throw');
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testWillMountVolumeWithWrongPath()
    {
        $mock = Mock::new($this->assert())
            ->willMountVolume('foo', '/bar');

        try {
            $mock
                ->volumes()
                ->mount(Volumes\Name::of('foo'), Path::of('/foo'));
        } catch (\Throwable $e) {
            $this->assertInstanceOf(Failure::class, $e);

            return;
        }

        $this->fail('It should throw');
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testWillFailToMountVolume()
    {
        $mock = Mock::new($this->assert())
            ->willFailToMountVolume('foo', '/bar');

        $this->assertFalse(
            $mock
                ->volumes()
                ->mount(Volumes\Name::of('foo'), Path::of('/bar'))
                ->match(
                    static fn() => true,
                    static fn() => false,
                ),
        );
        $this
            ->assert()
            ->not()
            ->throws(static fn() => $mock->assert());
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testWillFailToMountVolumeWithWrongName()
    {
        $mock = Mock::new($this->assert())
            ->willFailToMountVolume('foo', '/bar');

        try {
            $mock
                ->volumes()
                ->mount(Volumes\Name::of('bar'), Path::of('/bar'));
        } catch (\Throwable $e) {
            $this->assertInstanceOf(Failure::class, $e);

            return;
        }

        $this->fail('It should throw');
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testWillFailToMountVolumeWithWrongPath()
    {
        $mock = Mock::new($this->assert())
            ->willFailToMountVolume('foo', '/bar');

        try {
            $mock
                ->volumes()
                ->mount(Volumes\Name::of('foo'), Path::of('/foo'));
        } catch (\Throwable $e) {
            $this->assertInstanceOf(Failure::class, $e);

            return;
        }

        $this->fail('It should throw');
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testUnexpectedMountVolume()
    {
        $mock = Mock::new($this->assert());

        try {
            $mock
                ->volumes()
                ->mount(Volumes\Name::of('foo'), Path::of('/bar'));
        } catch (\Throwable $e) {
            $this->assertInstanceOf(Failure::class, $e);

            return;
        }

        $this->fail('It should throw');
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testUncalledMountVolume()
    {
        $mock = Mock::new($this->assert())
            ->willMountVolume('foo', '/bar');

        try {
            $mock->assert();
        } catch (\Throwable $e) {
            $this->assertInstanceOf(Failure::class, $e);

            return;
        }

        $this->fail('It should throw');
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testWillUnmountVolume()
    {
        $mock = Mock::new($this->assert())
            ->willUnmountVolume('foo');

        $this->assertInstanceOf(
            SideEffect::class,
            $mock
                ->volumes()
                ->unmount(Volumes\Name::of('foo'))
                ->unwrap(),
        );
        $this
            ->assert()
            ->not()
            ->throws(static fn() => $mock->assert());
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testWillUnmountVolumeWithWrongName()
    {
        $mock = Mock::new($this->assert())
            ->willUnmountVolume('foo');

        try {
            $mock
                ->volumes()
                ->unmount(Volumes\Name::of('bar'));
        } catch (\Throwable $e) {
            $this->assertInstanceOf(Failure::class, $e);

            return;
        }

        $this->fail('It should throw');
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testWillFailToUnmountVolume()
    {
        $mock = Mock::new($this->assert())
            ->willFailToUnmountVolume('foo');

        $this->assertFalse(
            $mock
                ->volumes()
                ->unmount(Volumes\Name::of('foo'))
                ->match(
                    static fn() => true,
                    static fn() => false,
                ),
        );
        $this
            ->assert()
            ->not()
            ->throws(static fn() => $mock->assert());
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testWillFailToUnmountVolumeWithWrongName()
    {
        $mock = Mock::new($this->assert())
            ->willFailToUnmountVolume('foo');

        try {
            $mock
                ->volumes()
                ->unmount(Volumes\Name::of('bar'));
        } catch (\Throwable $e) {
            $this->assertInstanceOf(Failure::class, $e);

            return;
        }

        $this->fail('It should throw');
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testUnexpectedUnmountVolume()
    {
        $mock = Mock::new($this->assert());

        try {
            $mock
                ->volumes()
                ->unmount(Volumes\Name::of('foo'));
        } catch (\Throwable $e) {
            $this->assertInstanceOf(Failure::class, $e);

            return;
        }

        $this->fail('It should throw');
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testUncalledUnmountVolume()
    {
        $mock = Mock::new($this->assert())
            ->willUnmountVolume('foo');

        try {
            $mock->assert();
        } catch (\Throwable $e) {
            $this->assertInstanceOf(Failure::class, $e);

            return;
        }

        $this->fail('It should throw');
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testProcessKillIsAlwaysSuccessful()
    {
        $mock = Mock::new($this->assert());

        $this->assertInstanceOf(
            SideEffect::class,
            $mock
                ->processes()
                ->kill(
                    new Pid(2),
                    Signal::kill,
                )
                ->unwrap(),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testWillExecute()
    {
        $expected = Command::foreground('echo');

        $mock = Mock::new($this->assert())
            ->willExecute(
                fn($command) => $this->assertSame($expected, $command),
            );

        $process = $mock
            ->processes()
            ->execute($expected)
            ->unwrap();

        $this->assertSame(2, $process->pid()->match(
            static fn($pid) => $pid->toInt(),
            static fn() => null,
        ));
        $this->assertInstanceOf(
            Success::class,
            $process
                ->wait()
                ->match(
                    static fn($success) => $success,
                    static fn($error) => $error,
                ),
        );
        $this->assertCount(0, $process->output());
        $this
            ->assert()
            ->not()
            ->throws(static fn() => $mock->assert());
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testWillExecuteSuccess()
    {
        $expected = Command::foreground('echo');
        $output = Sequence::of();

        $mock = Mock::new($this->assert())
            ->willExecute(
                fn($command) => $this->assertSame($expected, $command),
                static fn($_, $build) => $build->success($output),
            );

        $process = $mock
            ->processes()
            ->execute($expected)
            ->unwrap();

        $this->assertInstanceOf(
            Success::class,
            $process
                ->wait()
                ->match(
                    static fn($success) => $success,
                    static fn($error) => $error,
                ),
        );
        $this->assertSame($output, $process->output());
        $this
            ->assert()
            ->not()
            ->throws(static fn() => $mock->assert());
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testWillExecuteSignaled()
    {
        $expected = Command::foreground('echo');
        $output = Sequence::of();

        $mock = Mock::new($this->assert())
            ->willExecute(
                fn($command) => $this->assertSame($expected, $command),
                static fn($_, $build) => $build->signaled($output),
            );

        $process = $mock
            ->processes()
            ->execute($expected)
            ->unwrap();

        $this->assertInstanceOf(
            Signaled::class,
            $process
                ->wait()
                ->match(
                    static fn($success) => $success,
                    static fn($error) => $error,
                ),
        );
        $this->assertSame($output, $process->output());
        $this
            ->assert()
            ->not()
            ->throws(static fn() => $mock->assert());
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testWillExecuteTimedOut()
    {
        $expected = Command::foreground('echo');
        $output = Sequence::of();

        $mock = Mock::new($this->assert())
            ->willExecute(
                fn($command) => $this->assertSame($expected, $command),
                static fn($_, $build) => $build->timedOut($output),
            );

        $process = $mock
            ->processes()
            ->execute($expected)
            ->unwrap();

        $this->assertInstanceOf(
            TimedOut::class,
            $process
                ->wait()
                ->match(
                    static fn($success) => $success,
                    static fn($error) => $error,
                ),
        );
        $this->assertSame($output, $process->output());
        $this
            ->assert()
            ->not()
            ->throws(static fn() => $mock->assert());
    }

    #[Group('ci')]
    #[Group('local')]
    #[Group('wip')]
    public function testWillExecuteFailed()
    {
        $expected = Command::foreground('echo');
        $output = Sequence::of();

        $mock = Mock::new($this->assert())
            ->willExecute(
                fn($command) => $this->assertSame($expected, $command),
                static fn($_, $build) => $build->failed(1, $output),
            );

        $process = $mock
            ->processes()
            ->execute($expected)
            ->unwrap();

        $result = $process
            ->wait()
            ->match(
                static fn($success) => $success,
                static fn($error) => $error,
            );
        $this->assertInstanceOf(Failed::class, $result);
        $this->assertSame(1, $result->exitCode()->toInt());
        $this->assertSame($output, $process->output());
        $this
            ->assert()
            ->not()
            ->throws(static fn() => $mock->assert());
    }
}
