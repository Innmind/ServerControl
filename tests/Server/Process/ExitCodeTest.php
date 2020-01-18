<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\{
    Server\Process\ExitCode,
    Exception\OutOfRangeExitCode,
};
use PHPUnit\Framework\TestCase;

class ExitCodeTest extends TestCase
{
    public function testSuccessfulCode()
    {
        $exit = new ExitCode(0);

        $this->assertTrue($exit->isSuccessful());
        $this->assertSame(0, $exit->toInt());
        $this->assertSame('0', (string) $exit);
    }

    public function testErrorCode()
    {
        $exit = new ExitCode(255);

        $this->assertFalse($exit->isSuccessful());
        $this->assertSame(255, $exit->toInt());
        $this->assertSame('255', (string) $exit);
    }

    public function testThrowWhenCodeTooLow()
    {
        $this->expectException(OutOfRangeExitCode::class);

        new ExitCode(-1);
    }

    public function testThrowWhenCodeTooHigh()
    {
        $this->expectException(OutOfRangeExitCode::class);

        new ExitCode(256);
    }
}
