<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server\Command\Argument,
    Server\Command\Option,
    Server\Command\Overwrite,
    Server\Command\Append,
    Server\Command\Pipe,
    Exception\EmptyExecutableNotAllowed,
    Exception\EmptyEnvironmentKeyNotAllowed,
};
use Innmind\Stream\Readable;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    Map,
    Str,
};
use function Innmind\Immutable\join;

final class Command
{
    private string $executable;
    /** @var Sequence<Command\Parameter> */
    private Sequence $parameters;
    /** @var Map<string, string> */
    private Map $environment;
    private ?Path $workingDirectory = null;
    private ?Readable $input = null;
    /** @var Append|Overwrite */
    private ?object $redirection = null;
    private bool $background = false;

    private function __construct(bool $background, string $executable)
    {
        if (Str::of($executable)->empty()) {
            throw new EmptyExecutableNotAllowed;
        }

        $this->executable = $executable;
        $this->background = $background;
        /** @var Sequence<Command\Parameter> */
        $this->parameters = Sequence::of(Command\Parameter::class);
        /** @var Map<string, string> */
        $this->environment = Map::of('string', 'string');
    }

    /**
     * Will run the command in the background and will survive even if the
     * current process ends
     *
     * You will not have access to the process output nor if the process is
     * still running
     */
    public static function background(string $executable): self
    {
        return new self(true, $executable);
    }

    /**
     * Will run the command in a non blocking way but will be killed when the
     * current process ends
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

    public function withOption(string $key, string $value = null): self
    {
        $self = clone $this;
        $self->parameters = ($this->parameters)(Option::long($key, $value));

        return $self;
    }

    public function withShortOption(string $key, string $value = null): self
    {
        $self = clone $this;
        $self->parameters = ($this->parameters)(Option::short($key, $value));

        return $self;
    }

    public function withEnvironment(string $key, string $value): self
    {
        if (Str::of($key)->empty()) {
            throw new EmptyEnvironmentKeyNotAllowed;
        }

        $self = clone $this;
        $self->environment = ($this->environment)($key, $value);

        return $self;
    }

    public function withWorkingDirectory(Path $path): self
    {
        $self = clone $this;
        $self->workingDirectory = $path;

        return $self;
    }

    public function withInput(Readable $input): self
    {
        $self = clone $this;
        $self->input = $input;

        return $self;
    }

    public function overwrite(Path $path): self
    {
        $self = clone $this;
        $self->redirection = new Overwrite($path);

        return $self;
    }

    public function append(Path $path): self
    {
        $self = clone $this;
        $self->redirection = new Append($path);

        return $self;
    }

    public function pipe(self $command): self
    {
        $self = clone $this;

        if ($this->redirection) {
            $self->parameters = ($this->parameters)($this->redirection);
        }

        $self->parameters = $self
            ->parameters
            ->add(new Pipe)
            ->add(new Argument($command->executable))
            ->append($command->parameters);
        $self->environment = $this->environment->merge($command->environment);
        $self->redirection = $command->redirection;

        return $self;
    }

    /**
     * @return Map<string, string>
     */
    public function environment(): Map
    {
        return $this->environment;
    }

    public function hasWorkingDirectory(): bool
    {
        return $this->workingDirectory instanceof Path;
    }

    /** @psalm-suppress InvalidNullableReturnType */
    public function workingDirectory(): Path
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->workingDirectory;
    }

    public function hasInput(): bool
    {
        return $this->input instanceof Readable;
    }

    /** @psalm-suppress InvalidNullableReturnType */
    public function input(): Readable
    {
        /** @psalm-suppress NullableReturnStatement */
        return $this->input;
    }

    public function toBeRunInBackground(): bool
    {
        return $this->background;
    }

    public function toString(): string
    {
        $string = $this->executable;

        if ($this->parameters->size() > 0) {
            $parameters = $this->parameters->mapTo(
                'string',
                static fn($parameter): string => $parameter->toString(),
            );
            $string .= ' '.join(' ', $parameters)->toString();
        }

        if ($this->redirection) {
            $string .= ' '.$this->redirection->toString();
        }

        return $string;
    }
}
