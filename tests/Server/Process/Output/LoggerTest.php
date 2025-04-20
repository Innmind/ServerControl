<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process\Output;

use Innmind\Server\Control\Server\{
    Process\Output\Logger,
    Process\Output\Chunk,
    Process\Output\Type,
    Process\Output,
    Command,
};
use Innmind\Immutable\{
    Map,
    Str,
    SideEffect,
    Sequence,
};
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Output::class,
            Logger::psr(
                $this->createMock(Output::class),
                Command::foreground('echo'),
                $this->createMock(LoggerInterface::class),
            ),
        );
    }

    public function testLogOutput()
    {
        $output = Logger::psr(
            $inner = $this->createMock(Output::class),
            Command::foreground('echo'),
            $logger = $this->createMock(LoggerInterface::class),
        );
        $logger
            ->expects($this->once())
            ->method('debug');
        $inner
            ->expects($this->once())
            ->method('foreach')
            ->with($this->callback(static function($callback) {
                $callback(Chunk::of(Str::of(''), Type::output));

                return true;
            }))
            ->willReturn(new SideEffect);

        $this->assertInstanceOf(
            SideEffect::class,
            $output->foreach(static fn() => null),
        );
    }

    public function testWarnErrors()
    {
        $output = Logger::psr(
            $inner = $this->createMock(Output::class),
            Command::foreground('echo'),
            $logger = $this->createMock(LoggerInterface::class),
        );
        $logger
            ->expects($this->once())
            ->method('warning');
        $inner
            ->expects($this->once())
            ->method('foreach')
            ->with($this->callback(static function($callback) {
                $callback(Chunk::of(Str::of(''), Type::error));

                return true;
            }))
            ->willReturn(new SideEffect);

        $this->assertInstanceOf(
            SideEffect::class,
            $output->foreach(static fn() => null),
        );
    }

    public function testReduce()
    {
        $output = Logger::psr(
            $inner = $this->createMock(Output::class),
            Command::foreground('echo'),
            $this->createMock(LoggerInterface::class),
        );
        $inner
            ->expects($this->once())
            ->method('reduce')
            ->willReturn($out = new \stdClass);

        $this->assertSame($out, $output->reduce(null, static fn() => null));
    }

    public function testFilterStillLogs()
    {
        $output = Logger::psr(
            $this->createMock(Output::class),
            Command::foreground('echo'),
            $this->createMock(LoggerInterface::class),
        );

        $this->assertInstanceOf(Logger::class, $output->filter(static fn() => true));
    }

    public function testGroupBy()
    {
        $output = Logger::psr(
            $inner = $this->createMock(Output::class),
            Command::foreground('echo'),
            $this->createMock(LoggerInterface::class),
        );
        $inner
            ->expects($this->once())
            ->method('groupBy')
            ->willReturn($map = Map::of());

        $this->assertSame($map, $output->groupBy(static fn() => ''));
    }

    public function testPartition()
    {
        $output = Logger::psr(
            $inner = $this->createMock(Output::class),
            Command::foreground('echo'),
            $this->createMock(LoggerInterface::class),
        );
        $inner
            ->expects($this->once())
            ->method('partition')
            ->willReturn($map = Map::of());

        $this->assertSame($map, $output->partition(static fn() => true));
    }

    public function testToString()
    {
        $output = Logger::psr(
            $inner = $this->createMock(Output::class),
            Command::foreground('echo'),
            $this->createMock(LoggerInterface::class),
        );
        $inner
            ->expects($this->once())
            ->method('toString')
            ->willReturn('foo');

        $this->assertSame('foo', $output->toString());
    }

    public function testChunks()
    {
        $output = Logger::psr(
            $inner = $this->createMock(Output::class),
            Command::foreground('echo'),
            $this->createMock(LoggerInterface::class),
        );
        $inner
            ->expects($this->once())
            ->method('chunks')
            ->willReturn($chunks = Sequence::of());

        $this->assertSame($chunks, $output->chunks());
    }
}
