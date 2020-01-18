<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process\Output;

use Innmind\Server\Control\{
    Server\Process\Output,
    Exception\InvalidOutputMap,
};
use Innmind\Immutable\{
    Map,
    Str,
};
use function Innmind\Immutable\join;

final class StaticOutput implements Output
{
    private Map $output;

    public function __construct(Map $output)
    {
        if (
            (string) $output->keyType() !== Str::class ||
            (string) $output->valueType() !== Type::class
        ) {
            throw new InvalidOutputMap;
        }

        $this->output = $output;
    }

    public function foreach(callable $function): Output
    {
        $this->output->foreach($function);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function reduce($carry, callable $reducer)
    {
        return $this->output->reduce($carry, $reducer);
    }

    public function filter(callable $predicate): Output
    {
        return new self(
            $this->output->filter($predicate)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy(callable $discriminator): Map
    {
        $groups = $this->output->groupBy($discriminator);

        return $groups->reduce(
            Map::of($groups->keyType(), Output::class),
            static function(Map $groups, $discriminent, Map $discriminated): Map {
                return $groups->put(
                    $discriminent,
                    new self($discriminated)
                );
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function partition(callable $predicate): Map
    {
        $partitions = $this->output->partition($predicate);

        return $partitions->reduce(
            Map::of($partitions->keyType(), Output::class),
            static function(Map $partitions, bool $bool, Map $discriminated): Map {
                return $partitions->put(
                    $bool,
                    new self($discriminated)
                );
            }
        );
    }

    public function toString(): string
    {
        $bits = $this->output->keys()->mapTo(
            'string',
            fn(Str $bit): string => $bit->toString(),
        );

        return join('', $bits)->toString();
    }
}
