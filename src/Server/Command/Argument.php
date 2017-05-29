<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Command;

use Innmind\Server\Control\Exception\EmptyArgumentNotAllowed;

final class Argument
{
    private $value;

    public function __construct(string $value)
    {
        if (empty($value)) {
            throw new EmptyArgumentNotAllowed;
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
