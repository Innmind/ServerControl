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
        $this->assertSame(\SIGHUP, Signal::hangUp()->toInt());
        $this->assertSame((string) \SIGHUP, Signal::hangUp()->toString());
    }

    public function testInterrupt()
    {
        $this->assertInstanceOf(Signal::class, Signal::interrupt());
        $this->assertSame(\SIGINT, Signal::interrupt()->toInt());
        $this->assertSame((string) \SIGINT, Signal::interrupt()->toString());
    }

    public function testQuit()
    {
        $this->assertInstanceOf(Signal::class, Signal::quit());
        $this->assertSame(\SIGQUIT, Signal::quit()->toInt());
        $this->assertSame((string) \SIGQUIT, Signal::quit()->toString());
    }

    public function testAbort()
    {
        $this->assertInstanceOf(Signal::class, Signal::abort());
        $this->assertSame(\SIGABRT, Signal::abort()->toInt());
        $this->assertSame((string) \SIGABRT, Signal::abort()->toString());
    }

    public function testKill()
    {
        $this->assertInstanceOf(Signal::class, Signal::kill());
        $this->assertSame(\SIGKILL, Signal::kill()->toInt());
        $this->assertSame((string) \SIGKILL, Signal::kill()->toString());
    }

    public function testAlarm()
    {
        $this->assertInstanceOf(Signal::class, Signal::alarm());
        $this->assertSame(\SIGALRM, Signal::alarm()->toInt());
        $this->assertSame((string) \SIGALRM, Signal::alarm()->toString());
    }

    public function testTerminate()
    {
        $this->assertInstanceOf(Signal::class, Signal::terminate());
        $this->assertSame(\SIGTERM, Signal::terminate()->toInt());
        $this->assertSame((string) \SIGTERM, Signal::terminate()->toString());
    }
}
