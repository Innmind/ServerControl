<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process\Output;

use Innmind\Server\Control\{
    Server\Process\Output\Output,
    Server\Process\Output\Type,
    Server\Process\Output as OutputInterface,
};
use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
};
use PHPUnit\Framework\TestCase;

class OutputTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            OutputInterface::class,
            new Output(Sequence::of('array'))
        );
    }

    public function testThrowWhenInvalidSequence()
    {
        $this->expectException(\TypeError::class);

        new Output(Sequence::of('string'));
    }

    public function testForeach()
    {
        $output = new Output(
            Sequence::of(
                'array',
                [Str::of('0'), Type::output()],
                [Str::of('1'), Type::output()],
                [Str::of('2'), Type::output()],
            ),
        );
        $count = 0;

        $this->assertNull(
            $output->foreach(function(Str $data, Type $type) use (&$count) {
                $this->assertSame((string) $count, $data->toString());
                ++$count;
            })
        );
        $this->assertSame(3, $count);
    }

    public function testReduce()
    {
        $output = new Output(
            Sequence::of(
                'array',
                [Str::of('0'), Type::output()],
                [Str::of('1'), Type::output()],
                [Str::of('2'), Type::output()],
            ),
        );

        $this->assertSame(
            3,
            $output->reduce(
                0,
                function(int $carry, Str $data, Type $type) {
                    return $carry + (int) $data->toString();
                }
            )
        );
    }

    public function testFilter()
    {
        $output = new Output(
            Sequence::of(
                'array',
                [Str::of('0'), Type::output()],
                [Str::of('1'), Type::output()],
                [Str::of('2'), Type::output()],
            ),
        );
        $output2 = $output->filter(function(Str $data, Type $type) {
            return (int) $data->toString() % 2 === 0;
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
                'array',
                [Str::of('0'), Type::output()],
                [Str::of('1'), Type::output()],
                [Str::of('2'), Type::output()],
            ),
        );
        $groups = $output->groupBy(function(Str $data, Type $type) {
            return (int) $data->toString() % 2;
        });

        $this->assertInstanceOf(Map::class, $groups);
        $this->assertSame('int', (string) $groups->keyType());
        $this->assertSame(OutputInterface::class, (string) $groups->valueType());
        $this->assertCount(2, $groups);
        $this->assertSame('02', $groups->get(0)->toString());
        $this->assertSame('1', $groups->get(1)->toString());
    }

    public function testPartition()
    {
        $output = new Output(
            Sequence::of(
                'array',
                [Str::of('0'), Type::output()],
                [Str::of('1'), Type::output()],
                [Str::of('2'), Type::output()],
            ),
        );
        $partitions = $output->partition(function(Str $data, Type $type) {
            return (int) $data->toString() % 2 === 0;
        });

        $this->assertInstanceOf(Map::class, $partitions);
        $this->assertSame('bool', (string) $partitions->keyType());
        $this->assertSame(OutputInterface::class, (string) $partitions->valueType());
        $this->assertCount(2, $partitions);
        $this->assertSame('02', $partitions->get(true)->toString());
        $this->assertSame('1', $partitions->get(false)->toString());
    }

    public function testStringCast()
    {
        $output = new Output(
            Sequence::of(
                'array',
                [Str::of('0'), Type::output()],
                [Str::of('1'), Type::output()],
                [Str::of('2'), Type::output()],
            ),
        );

        $this->assertSame('012', $output->toString());
    }
}
