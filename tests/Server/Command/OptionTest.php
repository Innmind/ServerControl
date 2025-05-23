<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Command;

use Innmind\Server\Control\Server\Command\Option;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class OptionTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testShort()
    {
        $option = Option::short('e');

        $this->assertInstanceOf(Option::class, $option);
        $this->assertSame("'-e'", $option->toString());

        $option = Option::short('e', 'dev');

        $this->assertInstanceOf(Option::class, $option);
        $this->assertSame("'-e' 'dev'", $option->toString());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testLong()
    {
        $option = Option::long('env');

        $this->assertInstanceOf(Option::class, $option);
        $this->assertSame("'--env'", $option->toString());

        $option = Option::long('env', 'dev');

        $this->assertInstanceOf(Option::class, $option);
        $this->assertSame("'--env=dev'", $option->toString());
    }
}
