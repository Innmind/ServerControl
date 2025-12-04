<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Command;

use Innmind\Server\Control\Server\Command\Str;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{
    DataProvider,
    Group,
};

class StrTest extends TestCase
{
    #[DataProvider('cases')]
    #[Group('ci')]
    #[Group('local')]
    public function testInterface(string $str, string $expected)
    {
        $this->assertSame($expected, Str::escape($str));
    }

    public static function cases(): array
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
