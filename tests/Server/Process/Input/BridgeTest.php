<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process\Input;

use Innmind\Server\Control\Server\Process\Input\Bridge;
use Innmind\Stream\Readable\Stream;
use PHPUnit\Framework\TestCase;

class BridgeTest extends TestCase
{
    public function testInterface()
    {
        $log = new Stream(\fopen('fixtures/symfony.log', 'r'));
        $bridge = new Bridge($log);

        $this->assertInstanceOf(\Iterator::class, $bridge);
        $this->assertSame(8192, \strlen($first = $bridge->current()));
        $this->assertSame(0, $bridge->key());
        $this->assertTrue($bridge->valid());
        $this->assertNull($bridge->next());
        $this->assertSame(8192, \strlen($second = $bridge->current()));
        $this->assertSame(8192, $bridge->key());
        $this->assertTrue($bridge->valid());
        $this->assertNotSame($first, $second);

        while ($bridge->valid()) {
            $bridge->next();
        }

        $this->assertFalse($bridge->valid());
        $this->assertSame(
            $log->size()->match(
                static fn($size) => $size->toInt(),
                static fn() => throw new \Exception,
            ),
            $bridge->key(),
        );
    }
}
