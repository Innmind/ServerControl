<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Command;

use Innmind\Server\Control\Server\Command\Redirection;
use Innmind\Url\Path;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class AppendTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $argument = Redirection::append(Path::of('some-value'));

        $this->assertSame(">> 'some-value'", $argument->toString());
    }
}
