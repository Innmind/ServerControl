<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Servers\Mock,
    Server,
    Server\Volumes,
};
use Innmind\Url\Path;
use Innmind\Immutable\SideEffect;
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
}
