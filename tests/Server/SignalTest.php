<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server;

use Innmind\Server\Control\Server\Signal;
use PHPUnit\Framework\TestCase;

class SignalTest extends TestCase
{
    public function testHangUp()
    {
        $this->assertInstanceOf(Signal::class, Signal::hangUp());
        $this->assertSame(Signal::HANG_UP, Signal::hangUp()->toInt());
        $this->assertSame((string) Signal::HANG_UP, Signal::hangUp()->toString());
    }

    public function testInterrupt()
    {
        $this->assertInstanceOf(Signal::class, Signal::interrupt());
        $this->assertSame(Signal::INTERRUPT, Signal::interrupt()->toInt());
        $this->assertSame((string) Signal::INTERRUPT, Signal::interrupt()->toString());
    }

    public function testQuit()
    {
        $this->assertInstanceOf(Signal::class, Signal::quit());
        $this->assertSame(Signal::QUIT, Signal::quit()->toInt());
        $this->assertSame((string) Signal::QUIT, Signal::quit()->toString());
    }

    public function testAbort()
    {
        $this->assertInstanceOf(Signal::class, Signal::abort());
        $this->assertSame(Signal::ABORT, Signal::abort()->toInt());
        $this->assertSame((string) Signal::ABORT, Signal::abort()->toString());
    }

    public function testKill()
    {
        $this->assertInstanceOf(Signal::class, Signal::kill());
        $this->assertSame(Signal::KILL, Signal::kill()->toInt());
        $this->assertSame((string) Signal::KILL, Signal::kill()->toString());
    }

    public function testAlarm()
    {
        $this->assertInstanceOf(Signal::class, Signal::alarm());
        $this->assertSame(Signal::ALARM, Signal::alarm()->toInt());
        $this->assertSame((string) Signal::ALARM, Signal::alarm()->toString());
    }

    public function testTerminate()
    {
        $this->assertInstanceOf(Signal::class, Signal::terminate());
        $this->assertSame(Signal::TERMINATE, Signal::terminate()->toInt());
        $this->assertSame((string) Signal::TERMINATE, Signal::terminate()->toString());
    }
}
