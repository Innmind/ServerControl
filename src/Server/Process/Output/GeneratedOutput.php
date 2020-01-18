<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process\Output;

use Innmind\Server\Control\{
    Server\Process\Output,
    CannotGroupEmptyOutput,
};
use Innmind\Immutable\{
    Map,
    Sequence,
    Type as TypeDeterminator,
    Str,
};
use function Innmind\Immutable\join;
use Symfony\Component\Process\Process;

final class GeneratedOutput implements Output
{
    private \Generator $generator;
    /** @var Sequence<array{0: Str, 1: Type}> */
    private Sequence $output;

    public function __construct(\Generator $generator)
    {
        $this->generator = $generator;
        $this->output = Sequence::defer(
            'array',
            (static function(\Generator $generator) {
                foreach ($generator as $key => $value) {
                    yield [
                        Str::of((string) $value),
                        $key === Process::OUT ? Type::output() : Type::error(),
                    ];
                }
            })($generator),
        );
    }

    public function foreach(callable $function): Output
    {
        $this->output->foreach(static function(array $output) use ($function): void {
            $function($output[0], $output[1]);
        });

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function reduce($carry, callable $reducer)
    {
        return $this->output->reduce(
            $carry,
            static function($carry, array $output) use ($reducer)  {
                return $reducer($carry, $output[0], $output[1]);
            },
        );
    }

    public function filter(callable $predicate): Output
    {
        $output = $this->output->filter(static function(array $output) use ($predicate): bool {
            return $predicate($output[0], $output[1]);
        });

        return new StaticOutput($output);
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy(callable $discriminator): Map
    {
        $groups = $this->output->groupBy(static function(array $output) use ($discriminator) {
            return $discriminator($output[0], $output[1]);
        });

        return $groups->toMapOf(
            $groups->keyType(),
            Output::class,
            static function($key, Sequence $output): \Generator {
                yield $key => new StaticOutput($output);
            },
        );
    }

    /**
     * {@inheritdoc}
     */
    public function partition(callable $predicate): Map
    {
        return $this
            ->output
            ->partition(static function(array $output) use ($predicate): bool {
                return $predicate($output[0], $output[1]);
            })
            ->toMapOf(
                'bool',
                Output::class,
                static function(bool $bool, Sequence $output): \Generator {
                    yield $bool => new StaticOutput($output);
                },
            );
    }

    public function toString(): string
    {
        $output = $this->output->toSequenceOf(
            'string',
            fn(array $output): \Generator => yield $output[0]->toString(),
        );

        return join('', $output)->toString();
    }
}
