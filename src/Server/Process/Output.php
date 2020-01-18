<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Server\Process\Output\Type;
use Innmind\Immutable\{
    Map,
    Str,
};

interface Output
{
    /**
     * @param callable(Str, Type): void $function
     */
    public function foreach(callable $function): void;

    /**
     * @template C
     *
     * @param C $carry
     * @param callable(C, Str, Type): C $reducer
     *
     * @return C
     */
    public function reduce($carry, callable $reducer);

    /**
     * @param callable(Str, Type): bool $predicate
     */
    public function filter(callable $predicate): self;

    /**
     * @template G
     *
     * @param callable(Str, Type): G $discriminator
     *
     * @return Map<G, self>
     */
    public function groupBy(callable $discriminator): Map;

    /**
     * @param callable(Str, Type): bool $predicate
     *
     * @return Map<bool, self>
     */
    public function partition(callable $predicate): Map;
    public function toString(): string;
}
