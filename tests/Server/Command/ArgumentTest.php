<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Command;

use Innmind\Server\Control\Server\Command\Argument;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class ArgumentTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $argument = new Argument('some value');

        $this->assertSame("'some value'", $argument->toString());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testDoesntThrowWhenEmptyArgument()
    {
        $this->assertSame("''", (new Argument(''))->toString());
    }
}
