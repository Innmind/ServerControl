<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Command;

use Innmind\Server\Control\{
    Server\Command\Append,
    Exception\LogicException,
};
use PHPUnit\Framework\TestCase;

class AppendTest extends TestCase
{
    public function testInterface()
    {
        $argument = new Append('some value');

        $this->assertSame(">> 'some value'", (string) $argument);
    }

    public function testThrowWhenEmptyArgument()
    {
        $this->expectException(LogicException::class);

        new Append('');
    }
}
