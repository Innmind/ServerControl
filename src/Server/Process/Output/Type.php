<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process\Output;

final class Type
{
    public const OUTPUT = 'stdout';
    public const ERROR = 'stderr';

    private $value;

    private function __construct(string $type)
    {
        $this->value = $type;
    }

    public static function output(): self
    {
        return new self(self::OUTPUT);
    }

    public static function error(): self
    {
        return new self(self::ERROR);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
