<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Command;


final class Pipe
{
    public function __toString(): string
    {
        return '|';
    }
}
