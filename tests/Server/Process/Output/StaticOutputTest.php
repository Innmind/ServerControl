<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process\Output;

use Innmind\Server\Control\Server\Process\{
    Output\StaticOutput,
    Output
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
            new StaticOutput(new Map(Str::class, 'string'))
        );
    }

    /**
     * @expectedException Innmind\Server\Control\Exception\InvalidOutputMap
     */
    public function testThrowWhenInvalidMapKey()
    {
        new StaticOutput(new Map('string', 'string'));
    }

    /**
     * @expectedException Innmind\Server\Control\Exception\InvalidOutputMap
     */
    public function testThrowWhenInvalidMapValue()
    {
        new StaticOutput(new Map(Str::class, 'int'));
    }

    public function testForeach()
    {
        $output = new StaticOutput(
            (new Map(Str::class, 'string'))
                ->put(new Str('0'), Output::OUTPUT)
                ->put(new Str('1'), Output::OUTPUT)
                ->put(new Str('2'), Output::OUTPUT)
        );
        $count = 0;

        $this->assertSame(
            $output,
            $output->foreach(function(Str $data, string $type) use (&$count) {
                $this->assertSame((string) $count, (string) $data);
                ++$count;
            })
        );
        $this->assertSame(3, $count);
    }

    public function testReduce()
    {
        $output = new StaticOutput(
            (new Map(Str::class, 'string'))
                ->put(new Str('0'), Output::OUTPUT)
                ->put(new Str('1'), Output::OUTPUT)
                ->put(new Str('2'), Output::OUTPUT)
        );

        $this->assertSame(
            3,
            $output->reduce(
                0,
                function(int $carry, Str $data, string $type) {
                    return $carry + (int) (string) $data;
                }
            )
        );
    }

    public function testFilter()
    {
        $output = new StaticOutput(
            (new Map(Str::class, 'string'))
                ->put(new Str('0'), Output::OUTPUT)
                ->put(new Str('1'), Output::OUTPUT)
                ->put(new Str('2'), Output::OUTPUT)
        );
        $output2 = $output->filter(function(Str $data, string $type) {
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
            (new Map(Str::class, 'string'))
                ->put(new Str('0'), Output::OUTPUT)
                ->put(new Str('1'), Output::OUTPUT)
                ->put(new Str('2'), Output::OUTPUT)
        );
        $groups = $output->groupBy(function(Str $data, string $type) {
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
            (new Map(Str::class, 'string'))
                ->put(new Str('0'), Output::OUTPUT)
                ->put(new Str('1'), Output::OUTPUT)
                ->put(new Str('2'), Output::OUTPUT)
        );
        $partitions = $output->partition(function(Str $data, string $type) {
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
            (new Map(Str::class, 'string'))
                ->put(new Str('0'), Output::OUTPUT)
                ->put(new Str('1'), Output::OUTPUT)
                ->put(new Str('2'), Output::OUTPUT)
        );

        $this->assertSame('012', (string) $output);
    }
}
