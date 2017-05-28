<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Command;

use Innmind\Server\Control\Server\Command\Option;
use PHPUnit\Framework\TestCase;

class OptionTest extends TestCase
{
    public function testShort()
    {
        $option = Option::short('e');

        $this->assertInstanceOf(Option::class, $option);
        $this->assertSame('-e', (string) $option);

        $option = Option::short('e', 'dev');

        $this->assertInstanceOf(Option::class, $option);
        $this->assertSame('-e dev', (string) $option);
    }

    public function testLong()
    {
        $option = Option::long('env');

        $this->assertInstanceOf(Option::class, $option);
        $this->assertSame('--env', (string) $option);

        $option = Option::long('env', 'dev');

        $this->assertInstanceOf(Option::class, $option);
        $this->assertSame('--env=dev', (string) $option);
    }

    /**
     * @expectedException Innmind\Server\Control\Exception\EmptyOptionNotAllowed
     */
    public function testThrowWhenEmptyShortOptionKey()
    {
        Option::short('');
    }

    /**
     * @expectedException Innmind\Server\Control\Exception\EmptyOptionNotAllowed
     */
    public function testThrowWhenEmptyLongOptionKey()
    {
        Option::long('');
    }
}
