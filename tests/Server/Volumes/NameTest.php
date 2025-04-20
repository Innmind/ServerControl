<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Volumes;

use Innmind\Server\Control\Server\Volumes\Name;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class NameTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this
            ->forAll(Set::strings()->atLeast(1))
            ->then(function($name) {
                $this->assertSame($name, (new Name($name))->toString());
            });
    }
}
