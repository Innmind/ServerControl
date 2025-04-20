<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process\Output;

use Innmind\Server\Control\Server\Process\Output as OutputInterface;
use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
    SideEffect,
};

/**
 * @psalm-immutable
 */
final class Output implements OutputInterface
{
    /** @var Sequence<Chunk> */
    private Sequence $output;

    /**
     * @param Sequence<Chunk> $output
     */
    public function __construct(Sequence $output)
    {
        $this->output = $output;
    }

    /**
     * @param Sequence<Chunk> $chunks
     */
    public static function of(Sequence $chunks): self
    {
        return new self($chunks);
    }

    /**
     * @param callable(Chunk): void $function
     */
    #[\Override]
    public function foreach(callable $function): SideEffect
    {
        return $this->output->foreach($function);
    }

    /**
     * @template C
     *
     * @param C $carry
     * @param callable(C, Chunk): C $reducer
     *
     * @return C
     */
    #[\Override]
    public function reduce($carry, callable $reducer)
    {
        return $this->output->reduce(
            $carry,
            $reducer,
        );
    }

    /**
     * @param callable(Chunk): bool $predicate
     */
    #[\Override]
    public function filter(callable $predicate): OutputInterface
    {
        return new self($this->output->filter($predicate));
    }

    /**
     * @template G
     *
     * @param callable(Chunk): G $discriminator
     *
     * @return Map<G, OutputInterface>
     */
    #[\Override]
    public function groupBy(callable $discriminator): Map
    {
        /**
         * @var Map<G, OutputInterface>
         */
        return $this
            ->output
            ->groupBy($discriminator)
            ->map(static fn($_, $discriminated) => new self($discriminated));
    }

    /**
     * @param callable(Chunk): bool $predicate
     *
     * @return Map<bool, OutputInterface>
     */
    #[\Override]
    public function partition(callable $predicate): Map
    {
        /** @var Map<bool, OutputInterface> */
        return $this
            ->output
            ->partition($predicate)
            ->map(static fn($_, $output) => new self($output));
    }

    #[\Override]
    public function toString(): string
    {
        $bits = $this->output->map(
            static fn($chunk): string => $chunk->data()->toString(),
        );

        return Str::of('')->join($bits)->toString();
    }

    #[\Override]
    public function chunks(): Sequence
    {
        return $this->output;
    }
}
