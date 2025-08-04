<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Servers\Mock,
    Server,
};
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
}
