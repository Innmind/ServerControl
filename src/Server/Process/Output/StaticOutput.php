<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process\Output;

use Innmind\Server\Control\{
    Server\Process\Output,
    Exception\InvalidOutputMap
};
use Innmind\Immutable\{
    MapInterface,
    Map,
    Str
};

final class StaticOutput implements Output
{
    private $output;

    public function __construct(MapInterface $output)
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
    public function groupBy(callable $discriminator): MapInterface
    {
        $groups = $this->output->groupBy($discriminator);

        return $groups->reduce(
            new Map((string) $groups->keyType(), Output::class),
            static function(Map $groups, $discriminent, MapInterface $discriminated): Map {
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
    public function partition(callable $predicate): MapInterface
    {
        $partitions = $this->output->partition($predicate);

        return $partitions->reduce(
            new Map((string) $partitions->keyType(), Output::class),
            static function(Map $partitions, bool $bool, MapInterface $discriminated): Map {
                return $partitions->put(
                    $bool,
                    new self($discriminated)
                );
            }
        );
    }

    public function __toString(): string
    {
        return (string) $this->output->keys()->join('');
    }
}
