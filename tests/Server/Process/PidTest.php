<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Server\Process\Pid;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class PidTest extends TestCase
{
    public function testInterface()
    {
        $pid = new Pid(2);

        $this->assertSame(2, $pid->toInt());
        $this->assertSame('2', $pid->toString());
    }
}
