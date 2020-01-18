<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Command;

use Innmind\Server\Control\{
    Server\Command\Overwrite,
    Exception\LogicException
};
use PHPUnit\Framework\TestCase;

class OverwriteTest extends TestCase
{
    public function testInterface()
    {
        $argument = new Overwrite('some value');

        $this->assertSame("> 'some value'", $argument->toString());
    }

    public function testThrowWhenEmptyArgument()
    {
        $this->expectException(LogicException::class);

        new Overwrite('');
    }
}
