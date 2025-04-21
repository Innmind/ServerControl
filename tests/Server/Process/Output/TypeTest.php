<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process\Output;

use Innmind\Server\Control\Server\Process\Output\Type;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class TypeTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testOutput()
    {
        $this->assertSame('stdout', Type::output->toString());
    }

    #[Group('ci')]
    #[Group('local')]
    public function testError()
    {
        $this->assertSame('stderr', Type::error->toString());
    }
}
