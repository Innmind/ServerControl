<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process\Output;

use Innmind\Immutable\Str;

/**
 * @psalm-immutable
 */
final class Chunk
{
    private function __construct(
        private Str $data,
        private Type $type,
    ) {
    }

    /**
     * @psalm-pure
     */
    #[\NoDiscard]
    public static function of(Str $data, Type $type): self
    {
        return new self($data, $type);
    }

    #[\NoDiscard]
    public function data(): Str
    {
        return $this->data;
    }

    #[\NoDiscard]
    public function type(): Type
    {
        return $this->type;
    }
}
