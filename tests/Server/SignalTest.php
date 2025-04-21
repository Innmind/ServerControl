<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server;

use Innmind\Server\Control\Server\Signal;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class SignalTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testHangUp()
    {
        $this->assertSame(\SIGHUP, Signal::hangUp->toInt());
        $this->assertSame((string) \SIGHUP, Signal::hangUp->toString());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testInterrupt()
    {
        $this->assertSame(\SIGINT, Signal::interrupt->toInt());
        $this->assertSame((string) \SIGINT, Signal::interrupt->toString());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testQuit()
    {
        $this->assertSame(\SIGQUIT, Signal::quit->toInt());
        $this->assertSame((string) \SIGQUIT, Signal::quit->toString());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testAbort()
    {
        $this->assertSame(\SIGABRT, Signal::abort->toInt());
        $this->assertSame((string) \SIGABRT, Signal::abort->toString());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testKill()
    {
        $this->assertSame(\SIGKILL, Signal::kill->toInt());
        $this->assertSame((string) \SIGKILL, Signal::kill->toString());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testAlarm()
    {
        $this->assertSame(\SIGALRM, Signal::alarm->toInt());
        $this->assertSame((string) \SIGALRM, Signal::alarm->toString());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testTerminate()
    {
        $this->assertSame(\SIGTERM, Signal::terminate->toInt());
        $this->assertSame((string) \SIGTERM, Signal::terminate->toString());
    }
}
