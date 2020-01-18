<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Command;

use Innmind\Immutable\Str as S;

final class Str
{
    private string $value;

    public function __construct(string $string)
    {
        $this->value = (string) $this->escape(new S($string));
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @see Symfony\Component\Process\Process::escapeArgument()
     */
    private function escape(S $string): S
    {
        if ('\\' !== DIRECTORY_SEPARATOR) {
            return $string
                ->replace("'", "'\\''")
                ->prepend("'")
                ->append("'");
        }

        if ($string->length() === 0) {
            return new S('""');
        }

        if ($string->contains("\0")) {
            $string = $string->replace("\0", '?');
        }

        if (!$string->matches('/[\/()%!^"<>&|\s]/')) {
            return $string;
        }

        return $string
            ->pregReplace('/(\\\\+)$/', '$1$1')
            ->replace('"', '""')
            ->replace('^', '"^^"')
            ->replace('%', '"^%"')
            ->replace('!', '"^!"')
            ->replace("\n", '!LF!')
            ->prepend('"')
            ->append('"');
    }
}
