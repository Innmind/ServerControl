<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Server\Process\Output\Chunk;
use Innmind\Immutable\{
    Map,
    SideEffect,
    Sequence,
};

/**
 * @psalm-immutable
 */
interface Output
{
    /**
     * @param callable(Chunk): void $function
     */
    public function foreach(callable $function): SideEffect;

    /**
     * @template C
     *
     * @param C $carry
     * @param callable(C, Chunk): C $reducer
     *
     * @return C
     */
    public function reduce($carry, callable $reducer);

    /**
     * @param callable(Chunk): bool $predicate
     */
    public function filter(callable $predicate): self;

    /**
     * @template G
     *
     * @param callable(Chunk): G $discriminator
     *
     * @return Map<G, self>
     */
    public function groupBy(callable $discriminator): Map;

    /**
     * @param callable(Chunk): bool $predicate
     *
     * @return Map<bool, self>
     */
    public function partition(callable $predicate): Map;
    public function toString(): string;

    /**
     * @return Sequence<Chunk>
     */
    public function chunks(): Sequence;
}
