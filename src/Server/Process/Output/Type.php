<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process\Output;

final class Type
{
    public const OUTPUT = 'stdout';
    public const ERROR = 'stderr';

    private static ?self $output = null;
    private static ?self $error = null;

    private string $value;

    private function __construct(string $type)
    {
        $this->value = $type;
    }

    public static function output(): self
    {
        return self::$output ?? self::$output = new self(self::OUTPUT);
    }

    public static function error(): self
    {
        return self::$error ?? self::$error =  new self(self::ERROR);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
