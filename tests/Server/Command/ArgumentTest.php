<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Command;

use Innmind\Server\Control\Server\Command\Argument;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class ArgumentTest extends TestCase
{
    public function testInterface()
    {
        $argument = new Argument('some value');

        $this->assertSame("'some value'", $argument->toString());
    }

    public function testDoesntThrowWhenEmptyArgument()
    {
        $this->assertSame("''", (new Argument(''))->toString());
    }
}
