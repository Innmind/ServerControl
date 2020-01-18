<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process\Output;

use Innmind\Server\Control\{
    Server\Process\Output\StaticOutput,
    Server\Process\Output\Type,
    Server\Process\Output,
    Exception\InvalidOutputMap,
};
use Innmind\Immutable\{
    Map,
    Str,
    MapInterface
};
use PHPUnit\Framework\TestCase;

class StaticOutputTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Output::class,
            new StaticOutput(new Map(Str::class, Type::class))
        );
    }

    public function testThrowWhenInvalidMapKey()
    {
        $this->expectException(InvalidOutputMap::class);

        new StaticOutput(new Map('string', Type::class));
    }

    public function testThrowWhenInvalidMapValue()
    {
        $this->expectException(InvalidOutputMap::class);

        new StaticOutput(new Map(Str::class, 'int'));
    }

    public function testForeach()
    {
        $output = new StaticOutput(
            (new Map(Str::class, Type::class))
                ->put(new Str('0'), Type::output())
                ->put(new Str('1'), Type::output())
                ->put(new Str('2'), Type::output())
        );
        $count = 0;

        $this->assertSame(
            $output,
            $output->foreach(function(Str $data, Type $type) use (&$count) {
                $this->assertSame((string) $count, (string) $data);
                ++$count;
            })
        );
        $this->assertSame(3, $count);
    }

    public function testReduce()
    {
        $output = new StaticOutput(
            (new Map(Str::class, Type::class))
                ->put(new Str('0'), Type::output())
                ->put(new Str('1'), Type::output())
                ->put(new Str('2'), Type::output())
        );

        $this->assertSame(
            3,
            $output->reduce(
                0,
                function(int $carry, Str $data, Type $type) {
                    return $carry + (int) (string) $data;
                }
            )
        );
    }

    public function testFilter()
    {
        $output = new StaticOutput(
            (new Map(Str::class, Type::class))
                ->put(new Str('0'), Type::output())
                ->put(new Str('1'), Type::output())
                ->put(new Str('2'), Type::output())
        );
        $output2 = $output->filter(function(Str $data, Type $type) {
            return (int) (string) $data % 2 === 0;
        });

        $this->assertInstanceOf(Output::class, $output2);
        $this->assertNotSame($output, $output2);
        $this->assertSame('012', (string) $output);
        $this->assertSame('02', (string) $output2);
    }

    public function testGroupBy()
    {
        $output = new StaticOutput(
            (new Map(Str::class, Type::class))
                ->put(new Str('0'), Type::output())
                ->put(new Str('1'), Type::output())
                ->put(new Str('2'), Type::output())
        );
        $groups = $output->groupBy(function(Str $data, Type $type) {
            return (int) (string) $data % 2;
        });

        $this->assertInstanceOf(MapInterface::class, $groups);
        $this->assertSame('int', (string) $groups->keyType());
        $this->assertSame(Output::class, (string) $groups->valueType());
        $this->assertCount(2, $groups);
        $this->assertSame('02', (string) $groups->get(0));
        $this->assertSame('1', (string) $groups->get(1));
    }

    public function testPartition()
    {
        $output = new StaticOutput(
            (new Map(Str::class, Type::class))
                ->put(new Str('0'), Type::output())
                ->put(new Str('1'), Type::output())
                ->put(new Str('2'), Type::output())
        );
        $partitions = $output->partition(function(Str $data, Type $type) {
            return (int) (string) $data % 2 === 0;
        });

        $this->assertInstanceOf(MapInterface::class, $partitions);
        $this->assertSame('bool', (string) $partitions->keyType());
        $this->assertSame(Output::class, (string) $partitions->valueType());
        $this->assertCount(2, $partitions);
        $this->assertSame('02', (string) $partitions->get(true));
        $this->assertSame('1', (string) $partitions->get(false));
    }

    public function testStringCast()
    {
        $output = new StaticOutput(
            (new Map(Str::class, Type::class))
                ->put(new Str('0'), Type::output())
                ->put(new Str('1'), Type::output())
                ->put(new Str('2'), Type::output())
        );

        $this->assertSame('012', (string) $output);
    }
}
