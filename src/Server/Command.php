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
    /** @var non-empty-string */
    private string $executable;
    /** @var Sequence<Command\Parameter> */
    private Sequence $parameters;
    /** @var Map<string, string> */
    private Map $environment;
    /** @var Maybe<Path> */
    private Maybe $workingDirectory;
    /** @var Maybe<Content> */
    private Maybe $input;
    /** @var Maybe<Append>|Maybe<Overwrite> */
    private Maybe $redirection;
    private bool $background = false;
    /** @var Maybe<Second> */
    private Maybe $timeout;
    private bool $streamOutput = false;

    /**
     * @param non-empty-string $executable
     */
    private function __construct(bool $background, string $executable)
    {
        $this->executable = $executable;
        $this->background = $background;
        /** @var Sequence<Command\Parameter> */
        $this->parameters = Sequence::of();
        /** @var Map<string, string> */
        $this->environment = Map::of();
        /** @var Maybe<Path> */
        $this->workingDirectory = Maybe::nothing();
        /** @var Maybe<Content> */
        $this->input = Maybe::nothing();
        /** @var Maybe<Append>|Maybe<Overwrite> */
        $this->redirection = Maybe::nothing();
        /** @var Maybe<Second> */
        $this->timeout = Maybe::nothing();
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
    public static function background(string $executable): self
    {
        return new self(true, $executable);
    }

    /**
     * Will run the command in a non blocking way but will be killed when the
     * current process ends
     *
     * @psalm-pure
     *
     * @param non-empty-string $executable
     */
    public static function foreground(string $executable): self
    {
        return new self(false, $executable);
    }

    public function withArgument(string $value): self
    {
        $self = clone $this;
        $self->parameters = ($this->parameters)(new Argument($value));

        return $self;
    }

    /**
     * @param non-empty-string $key
     */
    public function withOption(string $key, string $value = null): self
    {
        $self = clone $this;
        $self->parameters = ($this->parameters)(Option::long($key, $value));

        return $self;
    }

    /**
     * @param non-empty-string $key
     */
    public function withShortOption(string $key, string $value = null): self
    {
        $self = clone $this;
        $self->parameters = ($this->parameters)(Option::short($key, $value));

        return $self;
    }

    /**
     * @param non-empty-string $key
     */
    public function withEnvironment(string $key, string $value): self
    {
        $self = clone $this;
        $self->environment = ($this->environment)($key, $value);

        return $self;
    }

    /**
     * @param Map<non-empty-string, string> $values
     */
    public function withEnvironments(Map $values): self
    {
        $self = clone $this;
        $self->environment = $this->environment->merge($values);

        return $self;
    }

    public function withWorkingDirectory(Path $path): self
    {
        $self = clone $this;
        $self->workingDirectory = Maybe::just($path);

        return $self;
    }

    public function withInput(Content $input): self
    {
        $self = clone $this;
        $self->input = Maybe::just($input);

        return $self;
    }

    public function overwrite(Path $path): self
    {
        $self = clone $this;
        $self->redirection = Maybe::just(new Overwrite($path));

        return $self;
    }

    public function append(Path $path): self
    {
        $self = clone $this;
        $self->redirection = Maybe::just(new Append($path));

        return $self;
    }

    public function pipe(self $command): self
    {
        $self = clone $this;

        $self->parameters = $this
            ->redirection
            ->match(
                fn($redirection) => ($this->parameters)($redirection),
                fn() => $this->parameters,
            )
            ->add(new Pipe)
            ->add(new Argument($command->executable))
            ->append($command->parameters);
        $self->environment = $this->environment->merge($command->environment);
        $self->redirection = $command->redirection;

        return $self;
    }

    public function timeoutAfter(Second $seconds): self
    {
        $self = clone $this;
        $self->timeout = Maybe::just($seconds);

        return $self;
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
    public function streamOutput(): self
    {
        $self = clone $this;
        $self->streamOutput = true;

        return $self;
    }

    /**
     * @return Map<string, string>
     */
    public function environment(): Map
    {
        return $this->environment;
    }

    /**
     * @return Maybe<Path>
     */
    public function workingDirectory(): Maybe
    {
        return $this->workingDirectory;
    }

    /**
     * @return Maybe<Content>
     */
    public function input(): Maybe
    {
        return $this->input;
    }

    public function toBeRunInBackground(): bool
    {
        return $this->background;
    }

    /**
     * @return Maybe<Second>
     */
    public function timeout(): Maybe
    {
        return $this->timeout;
    }

    public function outputToBeStreamed(): bool
    {
        return $this->streamOutput;
    }

    /**
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
