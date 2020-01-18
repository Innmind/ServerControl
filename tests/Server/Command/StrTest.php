<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Command;

use Innmind\Server\Control\Server\Command\Str;
use PHPUnit\Framework\TestCase;

class StrTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function testInterface(string $str, string $expected)
    {
        $this->assertSame($expected, (new Str($str))->toString());
    }

    public function cases(): array
    {
        return [
            ['a"b%c%', "'a\"b%c%'"],
            ['a"b^c^', "'a\"b^c^'"],
            ["a\nb'c", "'a\nb'\''c'"],
            ['a^b c!', "'a^b c!'"],
            ["a!b\tc", "'a!b\tc'"],
            ['a\\\\"\\"', '\'a\\\\"\\"\''],
            ['éÉèÈàÀöä', "'éÉèÈàÀöä'"],
        ];
    }
}
