<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server\Command\Argument,
    Server\Command\Option,
    Server\Command\Overwrite,
    Server\Command\Append,
    Server\Command\Pipe,
};
use Innmind\TimeContinuum\Period;
use Innmind\Filesystem\File\Content;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    Map,
    Str,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Command
{
    /**
     * @param non-empty-string $executable
     * @param Sequence<Command\Parameter> $parameters
     * @param Map<string, string> $environment
     * @param Maybe<Path> $workingDirectory
     * @param Maybe<Content> $input
     * @param Maybe<Append>|Maybe<Overwrite> $redirection
     * @param Maybe<Period> $timeout
     */
    private function __construct(
        private bool $background,
        private string $executable,
        private Sequence $parameters,
        private Map $environment,
        private Maybe $workingDirectory,
        private Maybe $input,
        private Maybe $redirection,
        private Maybe $timeout,
        private bool $streamOutput,
    ) {
    }

    /**
     * Will run the command in the background and will survive even if the
     * current process ends
     *
     * You will not have access to the process output nor if the process is
     * still running
     *
     * @psalm-pure
     *
     * @param non-empty-string $executable
     */
    #[\NoDiscard]
    public static function background(string $executable): self
    {
        /** @var Maybe<Path> */
        $workingDirectory = Maybe::nothing();
        /** @var Maybe<Content> */
        $input = Maybe::nothing();
        /** @var Maybe<Append>|Maybe<Overwrite> */
        $redirection = Maybe::nothing();
        /** @var Maybe<Period> */
        $timeout = Maybe::nothing();

        return new self(
            true,
            $executable,
            Sequence::of(),
            Map::of(),
            $workingDirectory,
            $input,
            $redirection,
            $timeout,
            false,
        );
    }

    /**
     * Will run the command in a non blocking way but will be killed when the
     * current process ends
     *
     * @psalm-pure
     *
     * @param non-empty-string $executable
     */
    #[\NoDiscard]
    public static function foreground(string $executable): self
    {
        /** @var Maybe<Path> */
        $workingDirectory = Maybe::nothing();
        /** @var Maybe<Content> */
        $input = Maybe::nothing();
        /** @var Maybe<Append>|Maybe<Overwrite> */
        $redirection = Maybe::nothing();
        /** @var Maybe<Period> */
        $timeout = Maybe::nothing();

        return new self(
            false,
            $executable,
            Sequence::of(),
            Map::of(),
            $workingDirectory,
            $input,
            $redirection,
            $timeout,
            false,
        );
    }

    #[\NoDiscard]
    public function withArgument(string $value): self
    {
        return new self(
            $this->background,
            $this->executable,
            ($this->parameters)(new Argument($value)),
            $this->environment,
            $this->workingDirectory,
            $this->input,
            $this->redirection,
            $this->timeout,
            $this->streamOutput,
        );
    }

    /**
     * @param non-empty-string $key
     */
    #[\NoDiscard]
    public function withOption(string $key, ?string $value = null): self
    {
        return new self(
            $this->background,
            $this->executable,
            ($this->parameters)(Option::long($key, $value)),
            $this->environment,
            $this->workingDirectory,
            $this->input,
            $this->redirection,
            $this->timeout,
            $this->streamOutput,
        );
    }

    /**
     * @param non-empty-string $key
     */
    #[\NoDiscard]
    public function withShortOption(string $key, ?string $value = null): self
    {
        return new self(
            $this->background,
            $this->executable,
            ($this->parameters)(Option::short($key, $value)),
            $this->environment,
            $this->workingDirectory,
            $this->input,
            $this->redirection,
            $this->timeout,
            $this->streamOutput,
        );
    }

    /**
     * @param non-empty-string $key
     */
    #[\NoDiscard]
    public function withEnvironment(string $key, string $value): self
    {
        return new self(
            $this->background,
            $this->executable,
            $this->parameters,
            ($this->environment)($key, $value),
            $this->workingDirectory,
            $this->input,
            $this->redirection,
            $this->timeout,
            $this->streamOutput,
        );
    }

    /**
     * @param Map<non-empty-string, string> $values
     */
    #[\NoDiscard]
    public function withEnvironments(Map $values): self
    {
        return new self(
            $this->background,
            $this->executable,
            $this->parameters,
            $this->environment->merge($values),
            $this->workingDirectory,
            $this->input,
            $this->redirection,
            $this->timeout,
            $this->streamOutput,
        );
    }

    #[\NoDiscard]
    public function withWorkingDirectory(Path $path): self
    {
        return new self(
            $this->background,
            $this->executable,
            $this->parameters,
            $this->environment,
            Maybe::just($path),
            $this->input,
            $this->redirection,
            $this->timeout,
            $this->streamOutput,
        );
    }

    #[\NoDiscard]
    public function withInput(Content $input): self
    {
        return new self(
            $this->background,
            $this->executable,
            $this->parameters,
            $this->environment,
            $this->workingDirectory,
            Maybe::just($input),
            $this->redirection,
            $this->timeout,
            $this->streamOutput,
        );
    }

    #[\NoDiscard]
    public function overwrite(Path $path): self
    {
        return new self(
            $this->background,
            $this->executable,
            $this->parameters,
            $this->environment,
            $this->workingDirectory,
            $this->input,
            Maybe::just(new Overwrite($path)),
            $this->timeout,
            $this->streamOutput,
        );
    }

    #[\NoDiscard]
    public function append(Path $path): self
    {
        return new self(
            $this->background,
            $this->executable,
            $this->parameters,
            $this->environment,
            $this->workingDirectory,
            $this->input,
            Maybe::just(new Append($path)),
            $this->timeout,
            $this->streamOutput,
        );
    }

    #[\NoDiscard]
    public function pipe(self $command): self
    {
        return new self(
            $this->background,
            $this->executable,
            $this
                ->redirection
                ->match(
                    $this->parameters,
                    fn() => $this->parameters,
                )
                ->add(new Pipe)
                ->add(new Argument($command->executable))
                ->append($command->parameters),
            $this->environment->merge($command->environment),
            $this->workingDirectory,
            $this->input,
            $command->redirection,
            $this->timeout,
            $this->streamOutput,
        );
    }

    #[\NoDiscard]
    public function timeoutAfter(Period $timeout): self
    {
        return new self(
            $this->background,
            $this->executable,
            $this->parameters,
            $this->environment,
            $this->workingDirectory,
            $this->input,
            $this->redirection,
            Maybe::just($timeout),
            $this->streamOutput,
        );
    }

    /**
     * By default the process output is kept in memory so you can iterate
     * multiple times over it (behaviour is always the same)
     *
     * By calling this method the output will be streamed once meaning if you
     * iterate over the output twice the second time it will fail.
     *
     * This is useful in the case you need to access the output but can't fit it
     * in memory like streaming large files.
     */
    #[\NoDiscard]
    public function streamOutput(): self
    {
        return new self(
            $this->background,
            $this->executable,
            $this->parameters,
            $this->environment,
            $this->workingDirectory,
            $this->input,
            $this->redirection,
            $this->timeout,
            true,
        );
    }

    /**
     * @internal
     *
     * @return Map<string, string>
     */
    public function environment(): Map
    {
        return $this->environment;
    }

    /**
     * @internal
     *
     * @return Maybe<Path>
     */
    public function workingDirectory(): Maybe
    {
        return $this->workingDirectory;
    }

    /**
     * @internal
     *
     * @return Maybe<Content>
     */
    public function input(): Maybe
    {
        return $this->input;
    }

    /**
     * @internal
     */
    public function toBeRunInBackground(): bool
    {
        return $this->background;
    }

    /**
     * @internal
     *
     * @return Maybe<Period>
     */
    public function timeout(): Maybe
    {
        return $this->timeout;
    }

    /**
     * @internal
     */
    public function outputToBeStreamed(): bool
    {
        return $this->streamOutput;
    }

    /**
     * @internal
     *
     * @return non-empty-string
     */
    public function toString(): string
    {
        $string = $this->executable;

        if ($this->parameters->size() > 0) {
            $parameters = $this->parameters->map(
                static fn($parameter): string => $parameter->toString(),
            );
            $string .= ' '.Str::of(' ')->join($parameters)->toString();
        }

        return $this
            ->redirection
            ->map(static fn($redirection) => $redirection->toString())
            ->map(static fn($redirection) => ' '.$redirection)
            ->map(static fn($redirection) => $string.$redirection)
            ->match(
                static fn($string) => $string,
                static fn() => $string,
            );
    }
}
