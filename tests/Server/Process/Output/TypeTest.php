<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process\Output;

use Innmind\Server\Control\Server\Process\Output\Type;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class TypeTest extends TestCase
{
    public function testOutput()
    {
        $this->assertSame('stdout', Type::output->toString());
    }

    public function testError()
    {
        $this->assertSame('stderr', Type::error->toString());
    }
}
