<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Volumes;

use Innmind\Server\Control\Server\Volumes\Name;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};
use PHPUnit\Framework\Attributes\Group;

class NameTest extends TestCase
{
    use BlackBox;

    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $this
            ->forAll(Set::strings()->atLeast(1))
            ->then(function($name) {
                $this->assertSame($name, (new Name($name))->toString());
            });
    }
}
