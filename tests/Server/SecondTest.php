<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server\Second,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class SecondTest extends TestCase
{
    use BlackBox;

    public function testCanBeAnyPositiveValue()
    {
        $this
            ->forAll(Set\Integers::above(1))
            ->then(function($second) {
                $this->assertSame($second, (new Second($second))->toInt());
            });
    }

    public function testZeroHasNoMeaning()
    {
        $this->expectException(DomainException::class);

        new Second(0);
    }

    public function testNegativeValuesAreRejected()
    {
        $this
            ->forAll(Set\Integers::below(0))
            ->then(function($negative) {
                try {
                    new Second($negative);
                    $this->fail('it should throw');
                } catch (\Exception $e) {
                    $this->assertInstanceOf(DomainException::class, $e);
                }
            });
    }
}
