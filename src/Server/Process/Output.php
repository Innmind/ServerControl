<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Immutable\Map;

interface Output
{
    public function foreach(callable $function): self;

    /**
     * @param mixed $carry
     *
     * @return mixed
     */
    public function reduce($carry, callable $reducer);
    public function filter(callable $predicate): self;

    /**
     * @return Map<mixed, self>
     */
    public function groupBy(callable $discriminator): Map;

    /**
     * @return Map<bool, self>
     */
    public function partition(callable $predicate): Map;
    public function toString(): string;
}
