<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Command;

use Innmind\Server\Control\Server\Command\Pipe;
use PHPUnit\Framework\TestCase;

class PipeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertSame('|', (new Pipe)->toString());
    }
}
