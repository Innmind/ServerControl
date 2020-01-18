<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process\Output;

use Innmind\Server\Control\{
    Server\Process\Output,
    CannotGroupEmptyOutput,
};
use Innmind\Immutable\{
    Map,
    Type as TypeDeterminator,
    Str,
};
use function Innmind\Immutable\join;
use Symfony\Component\Process\Process;

final class GeneratedOutput implements Output
{
    private \Generator $generator;
    private Map $output;

    public function __construct(\Generator $generator)
    {
        $this->generator = $generator;
        $this->output = Map::of(Str::class, Type::class);
    }

    public function foreach(callable $function): Output
    {
        if ($this->loaded()) {
            $this->output->foreach($function);

            return $this;
        }

        while ($this->generator->valid()) {
            [$type, $data] = $this->read();
            $this->output = $this->output->put($data, $type);
            $function($data, $type);

            $this->generator->next();
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function reduce($carry, callable $reducer)
    {
        if ($this->loaded()) {
            return $this->output->reduce($carry, $reducer);
        }

        while ($this->generator->valid()) {
            [$type, $data] = $this->read();
            $this->output = $this->output->put($data, $type);
            $carry = $reducer($carry, $data, $type);

            $this->generator->next();
        }

        return $carry;
    }

    public function filter(callable $predicate): Output
    {
        if ($this->loaded()) {
            return new StaticOutput(
                $this->output->filter($predicate)
            );
        }

        $output = $this->output->clear();

        while ($this->generator->valid()) {
            [$type, $data] = $this->read();
            $this->output = $this->output->put($data, $type);

            if ($predicate($data, $type) === true) {
                $output = $output->put($data, $type);
            }

            $this->generator->next();
        }

        return new StaticOutput($output);
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy(callable $discriminator): Map
    {
        if ($this->loaded()) {
            $groups = $this->output->groupBy($discriminator);

            return $groups->reduce(
                Map::of($groups->keyType(), Output::class),
                function(Map $groups, $discriminent, Map $discriminated): Map {
                    return $groups->put(
                        $discriminent,
                        new StaticOutput($discriminated)
                    );
                }
            );
        }

        $output = null;

        while ($this->generator->valid()) {
            [$type, $data] = $this->read();
            $this->output = $this->output->put($data, $type);
            $discriminent = $discriminator($data, $type);

            if (is_null($output)) {
                $output = Map::of(
                    TypeDeterminator::determine($discriminent),
                    Map::class
                );
            }

            if (!$output->contains($discriminent)) {
                $output = $output->put(
                    $discriminent,
                    $this->output->clear()
                );
            }

            $output = $output->put(
                $discriminent,
                $output->get($discriminent)->put($data, $type)
            );

            $this->generator->next();
        }

        if (is_null($output)) {
            throw new CannotGroupEmptyOutput;
        }

        return $output->reduce(
            Map::of($output->keyType(), Output::class),
            function(Map $groups, $discriminent, Map $discriminated): Map {
                return $groups->put(
                    $discriminent,
                    new StaticOutput($discriminated)
                );
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function partition(callable $predicate): Map
    {
        if ($this->loaded()) {
            return $this
                ->output
                ->partition($predicate)
                ->reduce(
                    Map::of('bool', Output::class),
                    function(Map $partitions, bool $bool, Map $output): Map {
                        return $partitions->put(
                            $bool,
                            new StaticOutput($output)
                        );
                    }
                );
        }

        $output = Map::of('bool', Map::class)
            ->put(true, $this->output->clear())
            ->put(false, $this->output->clear());

        while ($this->generator->valid()) {
            [$type, $data] = $this->read();

            $result = $predicate($data, $type);

            $output = $output->put(
                $result,
                $output->get($result)->put($data, $type)
            );

            $this->generator->next();
        }

        return $output->reduce(
            Map::of('bool', Output::class),
            function(Map $partitions, bool $bool, Map $output): Map {
                return $partitions->put($bool, new StaticOutput($output));
            }
        );
    }

    public function toString(): string
    {
        if (!$this->loaded()) {
            $this->foreach(function(){}); //load the whole thing
        }

        $bits = $this->output->keys()->mapTo(
            'string',
            fn(Str $bit): string => $bit->toString(),
        );

        return join('', $bits)->toString();
    }

    private function loaded(): bool
    {
        return !$this->generator->valid();
    }

    private function type(string $type): Type
    {
        return $type === Process::OUT ? Type::output() : Type::error();
    }

    /**
     * @return [Type, Str]
     */
    private function read(): array
    {
        return [
            $this->type($this->generator->key()),
            Str::of((string) $this->generator->current()),
        ];
    }
}
