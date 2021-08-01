<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Command;

/**
 * @psalm-immutable
 */
interface Parameter
{
    public function toString(): string;
}
