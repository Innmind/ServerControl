<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server;

use Innmind\Server\Control\Server\Signal;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class SignalTest extends TestCase
{
    public function testHangUp()
    {
        $this->assertSame(\SIGHUP, Signal::hangUp->toInt());
        $this->assertSame((string) \SIGHUP, Signal::hangUp->toString());
    }

    public function testInterrupt()
    {
        $this->assertSame(\SIGINT, Signal::interrupt->toInt());
        $this->assertSame((string) \SIGINT, Signal::interrupt->toString());
    }

    public function testQuit()
    {
        $this->assertSame(\SIGQUIT, Signal::quit->toInt());
        $this->assertSame((string) \SIGQUIT, Signal::quit->toString());
    }

    public function testAbort()
    {
        $this->assertSame(\SIGABRT, Signal::abort->toInt());
        $this->assertSame((string) \SIGABRT, Signal::abort->toString());
    }

    public function testKill()
    {
        $this->assertSame(\SIGKILL, Signal::kill->toInt());
        $this->assertSame((string) \SIGKILL, Signal::kill->toString());
    }

    public function testAlarm()
    {
        $this->assertSame(\SIGALRM, Signal::alarm->toInt());
        $this->assertSame((string) \SIGALRM, Signal::alarm->toString());
    }

    public function testTerminate()
    {
        $this->assertSame(\SIGTERM, Signal::terminate->toInt());
        $this->assertSame((string) \SIGTERM, Signal::terminate->toString());
    }
}
