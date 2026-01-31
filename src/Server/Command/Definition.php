<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Command;

use Innmind\Time\Period;
use Innmind\Filesystem\File\Content;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    Map,
    Str,
    Maybe,
    Monoid\Concat,
};

/**
 * @psalm-immutable
 * @internal
 */
final class Definition implements Implementation
{
    /**
     * @param non-empty-string $executable
     * @param Sequence<Argument|Option> $parameters
     * @param Map<string, string> $environment
     * @param Maybe<Path> $workingDirectory
     * @param Maybe<Content> $input
     * @param Maybe<Redirection> $redirection
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
     * @psalm-pure
     * @internal
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
        /** @var Maybe<Redirection> */
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
     * @psalm-pure
     * @internal
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
        /** @var Maybe<Redirection> */
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
    #[\Override]
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

    #[\NoDiscard]
    #[\Override]
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

    #[\NoDiscard]
    #[\Override]
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

    #[\NoDiscard]
    #[\Override]
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

    #[\NoDiscard]
    #[\Override]
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
    #[\Override]
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
    #[\Override]
    public function overwrite(Path $path): self
    {
        return new self(
            $this->background,
            $this->executable,
            $this->parameters,
            $this->environment,
            $this->workingDirectory,
            $this->input,
            Maybe::just(Redirection::overwrite($path)),
            $this->timeout,
            $this->streamOutput,
        );
    }

    #[\NoDiscard]
    #[\Override]
    public function append(Path $path): self
    {
        return new self(
            $this->background,
            $this->executable,
            $this->parameters,
            $this->environment,
            $this->workingDirectory,
            $this->input,
            Maybe::just(Redirection::append($path)),
            $this->timeout,
            $this->streamOutput,
        );
    }

    #[\NoDiscard]
    #[\Override]
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

    #[\NoDiscard]
    #[\Override]
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
     * @return non-empty-string
     */
    #[\NoDiscard]
    public function executable(): string
    {
        return $this->executable;
    }

    /**
     * @return Sequence<Argument|Option>
     */
    #[\NoDiscard]
    public function parameters(): Sequence
    {
        return $this->parameters;
    }

    /**
     * @return Maybe<Redirection>
     */
    #[\NoDiscard]
    public function redirection(): Maybe
    {
        return $this->redirection;
    }

    #[\Override]
    public function environment(): Map
    {
        return $this->environment;
    }

    #[\Override]
    public function workingDirectory(): Maybe
    {
        return $this->workingDirectory;
    }

    #[\Override]
    public function input(): Maybe
    {
        return $this->input;
    }

    #[\Override]
    public function toBeRunInBackground(): bool
    {
        return $this->background;
    }

    #[\Override]
    public function timeout(): Maybe
    {
        return $this->timeout;
    }

    #[\Override]
    public function outputToBeStreamed(): bool
    {
        return $this->streamOutput;
    }

    #[\Override]
    public function toString(): string
    {
        /**
         * @psalm-suppress InvalidArgument Due to append
         * @var non-empty-string
         */
        return $this
            ->parameters
            ->append($this->redirection->toSequence())
            ->map(static fn($parameter) => ' '.$parameter->toString())
            ->map(Str::of(...))
            ->fold(Concat::monoid)
            ->prepend($this->executable)
            ->toString();
    }
}
