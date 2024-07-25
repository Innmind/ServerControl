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
    public static function of(Str $data, Type $type): self
    {
        return new self($data, $type);
    }

    public function data(): Str
    {
        return $this->data;
    }

    public function type(): Type
    {
        return $this->type;
    }
}
