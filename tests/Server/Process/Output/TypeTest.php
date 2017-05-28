<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process\Output;

use Innmind\Server\Control\Server\Process\Output\Type;
use PHPUnit\Framework\TestCase;

class TypeTest extends TestCase
{
    public function testOutput()
    {
        $output = Type::output();

        $this->assertInstanceOf(Type::class, $output);
        $this->assertSame(Type::OUTPUT, (string) $output);
    }

    public function testError()
    {
        $error = Type::error();

        $this->assertInstanceOf(Type::class, $error);
        $this->assertSame(Type::ERROR, (string) $error);
    }
}
