<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Server\Process\ExitCode;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class ExitCodeTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testSuccessfulCode()
    {
        $exit = new ExitCode(0);

        $this->assertTrue($exit->successful());
        $this->assertSame(0, $exit->toInt());
        $this->assertSame('0', $exit->toString());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testErrorCode()
    {
        $exit = new ExitCode(255);

        $this->assertFalse($exit->successful());
        $this->assertSame(255, $exit->toInt());
        $this->assertSame('255', $exit->toString());
    }
}
