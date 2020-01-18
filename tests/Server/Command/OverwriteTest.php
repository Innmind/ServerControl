<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Command;

use Innmind\Server\Control\Server\Command\Overwrite;
use Innmind\Url\Path;
use PHPUnit\Framework\TestCase;

class OverwriteTest extends TestCase
{
    public function testInterface()
    {
        $argument = new Overwrite(Path::of('some-value'));

        $this->assertSame("> 'some-value'", $argument->toString());
    }
}
