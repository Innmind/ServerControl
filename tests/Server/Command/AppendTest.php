<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Command;

use Innmind\Server\Control\Server\Command\Append;
use Innmind\Url\Path;
use PHPUnit\Framework\TestCase;

class AppendTest extends TestCase
{
    public function testInterface()
    {
        $argument = new Append(Path::of('some-value'));

        $this->assertSame(">> 'some-value'", $argument->toString());
    }
}
