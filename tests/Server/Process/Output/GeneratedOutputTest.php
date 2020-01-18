<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Process\Output;

use Innmind\Server\Control\Server\Process\{
    Output\GeneratedOutput,
    Output\Type,
    Output
};
use Innmind\Immutable\{
    Str,
    MapInterface
};
use Symfony\Component\Process\Process as SfProcess;
use PHPUnit\Framework\TestCase;

class GeneratedOutputTest extends TestCase
{
    private $output;

    public function setUp(): void
    {
        $this->output = new GeneratedOutput(
            (function(){
                foreach (range(0, 9) as $int) {
                    sleep(1);

                    yield SfProcess::OUT => $int;
                }
            })()
        );
    }

    public function testInterface()
    {
        $this->assertInstanceOf(Output::class, $this->output);
    }

    public function testForeach()
    {
        $start = time();
        $count = 0;
        $result = $this->output->foreach(function(Str $data, Type $type) use (&$count, $start) {
            $this->assertSame((string) $count, (string) $data);
            $this->assertTrue((time() - $start) > (int) (string) $data);
            ++$count;
        });

        $this->assertSame($this->output, $result);
        $this->assertSame(10, $count);
        $this->assertTrue((time() - $start) >= 10);

        $start = time();
        $this->output->foreach(function(){});
        $this->assertTrue((time() - $start) < 2);
    }

    public function testReduce()
    {
        $start = time();
        $result = $this->output->reduce(
            0,
            function(int $carry, Str $data, Type $type) use ($start) {
                $this->assertTrue((time() - $start) > (int) (string) $data);

                return $carry + (int) (string) $data;
            }
        );

        $this->assertSame(45, $result);
        $this->assertTrue((time() - $start) >= 10);

        $start = time();
        $this->output->reduce(0, function(){});
        $this->assertTrue((time() - $start) < 2);
    }

    public function testFilter()
    {
        $start = time();
        $result = $this->output->filter(
            function(Str $data, Type $type) use ($start) {
                $this->assertTrue((time() - $start) > (int) (string) $data);

                return (int) (string) $data % 2 === 0;
            }
        );

        $this->assertInstanceOf(Output::class, $result);
        $this->assertSame('0123456789', (string) $this->output);
        $this->assertSame('02468', (string) $result);
        $this->assertTrue((time() - $start) >= 10);

        $start = time();
        $this->output->filter(function(){return true;});
        $this->assertTrue((time() - $start) < 2);
    }

    public function testGroupBy()
    {
        $start = time();
        $result = $this->output->groupBy(
            function(Str $data, Type $type) use ($start) {
                $this->assertTrue((time() - $start) > (int) (string) $data);

                return (int) (string) $data % 2;
            }
        );

        $this->assertInstanceOf(MapInterface::class, $result);
        $this->assertSame('int', (string) $result->keyType());
        $this->assertSame(Output::class, (string) $result->valueType());
        $this->assertCount(2, $result);
        $this->assertSame('02468', (string) $result->get(0));
        $this->assertSame('13579', (string) $result->get(1));
        $this->assertTrue((time() - $start) >= 10);

        $start = time();
        $this->output->groupBy(function(Str $data){
            return (int) (string) $data % 2;
        });
        $this->assertTrue((time() - $start) < 2);
    }

    public function testPartition()
    {
        $start = time();
        $result = $this->output->partition(
            function(Str $data, Type $type) use ($start) {
                $this->assertTrue((time() - $start) > (int) (string) $data);

                return (int) (string) $data % 2 === 0;
            }
        );

        $this->assertInstanceOf(MapInterface::class, $result);
        $this->assertSame('bool', (string) $result->keyType());
        $this->assertSame(Output::class, (string) $result->valueType());
        $this->assertCount(2, $result);
        $this->assertSame('02468', (string) $result->get(true));
        $this->assertSame('13579', (string) $result->get(false));
        $this->assertTrue((time() - $start) >= 10);

        $start = time();
        $this->output->partition(function(Str $data){
            return (int) (string) $data % 2 === 0;
        });
        $this->assertTrue((time() - $start) < 2);
    }

    public function testStringCast()
    {
        $start = time();
        $this->assertSame('0123456789', (string) $this->output);
        $this->assertTrue((time() - $start) >= 10);

        $start = time();
        $this->assertSame('0123456789', (string) $this->output);
        $this->assertTrue((time() - $start) < 2);
    }
}
