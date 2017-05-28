<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Immutable\MapInterface;

interface Output
{
    public const OUTPUT = 'stdout';
    public const ERROR = 'stderr';

    public function foreach(callable $function): self;

    /**
     * @param mixed $carry
     *
     * @return mixed
     */
    public function reduce($carry, callable $reducer);
    public function filter(callable $predicate): self;

    /**
     * @return MapInterface<mixed, self>
     */
    public function groupBy(callable $discriminator): MapInterface;

    /**
     * @return MapInterface<bool, self>
     */
    public function partition(callable $predicate): MapInterface;
    public function __toString(): string;
}
