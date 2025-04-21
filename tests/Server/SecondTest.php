<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server;

use Innmind\Server\Control\Server\Second;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};

class SecondTest extends TestCase
{
    use BlackBox;

    public function testCanBeAnyPositiveValue()
    {
        $this
            ->forAll(Set::integers()->above(1))
            ->then(function($second) {
                $this->assertSame($second, (new Second($second))->toInt());
            });
    }
}
