<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process\Output;

/**
 * @psalm-immutable
 */
final class Type
{
    public const OUTPUT = 'stdout';
    public const ERROR = 'stderr';

    private string $value;

    private function __construct(string $type)
    {
        $this->value = $type;
    }

    /**
     * @psalm-pure
     */
    public static function output(): self
    {
        return new self(self::OUTPUT);
    }

    /**
     * @psalm-pure
     */
    public static function error(): self
    {
        return new self(self::ERROR);
    }

    public function equals(self $type): bool
    {
        return $this->value === $type->value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
