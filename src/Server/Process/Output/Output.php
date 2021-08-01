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
use function Innmind\Immutable\join;

final class Output implements OutputInterface
{
    /** @var Sequence<array{0: Str, 1: Type}> */
    private Sequence $output;

    /**
     * @param Sequence<array{0: Str, 1: Type}> $output
     */
    public function __construct(Sequence $output)
    {
        $this->output = $output;
    }

    /**
     * @param callable(Str, Type): void $function
     */
    public function foreach(callable $function): SideEffect
    {
        return $this->output->foreach(static function(array $output) use ($function): void {
            $function($output[0], $output[1]);
        });
    }

    /**
     * @template C
     *
     * @param C $carry
     * @param callable(C, Str, Type): C $reducer
     *
     * @return C
     */
    public function reduce($carry, callable $reducer)
    {
        /**
         * @psalm-suppress MissingClosureParamType
         * @psalm-suppress MixedArgument
         */
        return $this->output->reduce(
            $carry,
            static function($carry, array $output) use ($reducer) {
                return $reducer($carry, $output[0], $output[1]);
            },
        );
    }

    /**
     * @param callable(Str, Type): bool $predicate
     */
    public function filter(callable $predicate): OutputInterface
    {
        return new self($this->output->filter(
            static function(array $output) use ($predicate): bool {
                return $predicate($output[0], $output[1]);
            },
        ));
    }

    /**
     * @template G
     *
     * @param callable(Str, Type): G $discriminator
     *
     * @return Map<G, OutputInterface>
     */
    public function groupBy(callable $discriminator): Map
    {
        /**
         * @psalm-suppress MissingClosureParamType
         * @var Map<G, OutputInterface>
         */
        return $this
            ->output
            ->groupBy(static function(array $output) use ($discriminator) {
                return $discriminator($output[0], $output[1]);
            })
            ->map(static fn($_, $discriminated) => new self($discriminated));
    }

    /**
     * @param callable(Str, Type): bool $predicate
     *
     * @return Map<bool, OutputInterface>
     */
    public function partition(callable $predicate): Map
    {
        /** @var Map<bool, OutputInterface> */
        return $this
            ->output
            ->partition(static function(array $output) use ($predicate): bool {
                return $predicate($output[0], $output[1]);
            })
            ->map(static fn($_, $output) => new self($output));
    }

    public function toString(): string
    {
        $bits = $this->output->map(
            static fn(array $output): string => $output[0]->toString(),
        );

        return join('', $bits)->toString();
    }
}
