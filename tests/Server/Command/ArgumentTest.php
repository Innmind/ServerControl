<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Command;

use Innmind\Server\Control\Server\Command\Argument;
use PHPUnit\Framework\TestCase;

class ArgumentTest extends TestCase
{
    public function testInterface()
    {
        $argument = new Argument('some value');

        $this->assertSame('some value', (string) $argument);
    }

    /**
     * @expectedException Innmind\Server\Control\Exception\EmptyArgumentNotAllowed
     */
    public function testThrowWhenEmptyArgument()
    {
        new Argument('');
    }
}
