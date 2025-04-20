<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process\Output;

use Innmind\Server\Control\{
    Server\Process\Output\Output,
    Server\Process\Output\Chunk,
    Server\Process\Output\Type,
    Server\Process\Output as OutputInterface,
};
use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
    SideEffect,
};
use PHPUnit\Framework\TestCase;

class OutputTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            OutputInterface::class,
            new Output(Sequence::of()),
        );
    }

    public function testForeach()
    {
        $output = new Output(
            Sequence::of(
                Chunk::of(Str::of('0'), Type::output),
                Chunk::of(Str::of('1'), Type::output),
                Chunk::of(Str::of('2'), Type::output),
            ),
        );
        $count = 0;

        $this->assertInstanceOf(
            SideEffect::class,
            $output->foreach(function($chunk) use (&$count) {
                $this->assertSame((string) $count, $chunk->data()->toString());
                ++$count;
            }),
        );
        $this->assertSame(3, $count);
    }

    public function testReduce()
    {
        $output = new Output(
            Sequence::of(
                Chunk::of(Str::of('0'), Type::output),
                Chunk::of(Str::of('1'), Type::output),
                Chunk::of(Str::of('2'), Type::output),
            ),
        );

        $this->assertSame(
            3,
            $output->reduce(
                0,
                static function(int $carry, $chunk) {
                    return $carry + (int) $chunk->data()->toString();
                },
            ),
        );
    }

    public function testFilter()
    {
        $output = new Output(
            Sequence::of(
                Chunk::of(Str::of('0'), Type::output),
                Chunk::of(Str::of('1'), Type::output),
                Chunk::of(Str::of('2'), Type::output),
            ),
        );
        $output2 = $output->filter(static function($chunk) {
            return (int) $chunk->data()->toString() % 2 === 0;
        });

        $this->assertInstanceOf(OutputInterface::class, $output2);
        $this->assertNotSame($output, $output2);
        $this->assertSame('012', $output->toString());
        $this->assertSame('02', $output2->toString());
    }

    public function testGroupBy()
    {
        $output = new Output(
            Sequence::of(
                Chunk::of(Str::of('0'), Type::output),
                Chunk::of(Str::of('1'), Type::output),
                Chunk::of(Str::of('2'), Type::output),
            ),
        );
        $groups = $output->groupBy(static function($chunk) {
            return (int) $chunk->data()->toString() % 2;
        });

        $this->assertInstanceOf(Map::class, $groups);
        $this->assertCount(2, $groups);
        $this->assertSame('02', $groups->get(0)->match(
            static fn($output) => $output->toString(),
            static fn() => null,
        ));
        $this->assertSame('1', $groups->get(1)->match(
            static fn($output) => $output->toString(),
            static fn() => null,
        ));
    }

    public function testPartition()
    {
        $output = new Output(
            Sequence::of(
                Chunk::of(Str::of('0'), Type::output),
                Chunk::of(Str::of('1'), Type::output),
                Chunk::of(Str::of('2'), Type::output),
            ),
        );
        $partitions = $output->partition(static function($chunk) {
            return (int) $chunk->data()->toString() % 2 === 0;
        });

        $this->assertInstanceOf(Map::class, $partitions);
        $this->assertCount(2, $partitions);
        $this->assertSame('02', $partitions->get(true)->match(
            static fn($output) => $output->toString(),
            static fn() => null,
        ));
        $this->assertSame('1', $partitions->get(false)->match(
            static fn($output) => $output->toString(),
            static fn() => null,
        ));
    }

    public function testStringCast()
    {
        $output = new Output(
            Sequence::of(
                Chunk::of(Str::of('0'), Type::output),
                Chunk::of(Str::of('1'), Type::output),
                Chunk::of(Str::of('2'), Type::output),
            ),
        );

        $this->assertSame('012', $output->toString());
    }

    public function testChunks()
    {
        $output = new Output(
            $chunks = Sequence::of(),
        );

        $this->assertSame($chunks, $output->chunks());
    }
}
