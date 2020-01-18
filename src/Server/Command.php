<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server\Command\Argument,
    Server\Command\Option,
    Server\Command\Overwrite,
    Server\Command\Append,
    Server\Command\Pipe,
    Exception\LogicException,
    Exception\EmptyExecutableNotAllowed,
    Exception\EmptyEnvironmentKeyNotAllowed,
};
use Innmind\Stream\Readable;
use Innmind\Immutable\{
    Stream,
    Map,
    MapInterface,
};

final class Command
{
    private string $executable;
    private Stream $parameters;
    private Map $environment;
    private ?string $workingDirectory = null;
    private ?Readable $input = null;
    /** @var Append|Overwrite */
    private ?object $redirection = null;
    private bool $background = false;

    public function __construct(string $executable)
    {
        if (empty($executable)) {
            throw new EmptyExecutableNotAllowed;
        }

        $this->executable = $executable;
        $this->parameters = new Stream('object');
        $this->environment = new Map('string', 'string');
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
        $self = new self($executable);
        $self->background = true;

        return $self;
    }

    /**
     * Will run the command in a non blocking way but will be killed when the
     * current process ends
     */
    public static function foreground(string $executable): self
    {
        return new self($executable);
    }

    public function withArgument(string $value): self
    {
        $self = clone $this;
        $self->parameters = $this->parameters->add(new Argument($value));

        return $self;
    }

    public function withOption(string $key, string $value = null): self
    {
        $self = clone $this;
        $self->parameters = $this->parameters->add(Option::long($key, $value));

        return $self;
    }

    public function withShortOption(string $key, string $value = null): self
    {
        $self = clone $this;
        $self->parameters = $this->parameters->add(Option::short($key, $value));

        return $self;
    }

    public function withEnvironment(string $key, string $value): self
    {
        if (empty($key)) {
            throw new EmptyEnvironmentKeyNotAllowed;
        }

        $self = clone $this;
        $self->environment = $this->environment->put($key, $value);

        return $self;
    }

    public function withWorkingDirectory(string $path): self
    {
        if (empty($path)) {
            return $this;
        }

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

    public function overwrite(string $path): self
    {
        try {
            $argument = new Overwrite($path);
        } catch (LogicException $e) {
            return $this;
        }

        $self = clone $this;
        $self->redirection = $argument;

        return $self;
    }

    public function append(string $path): self
    {
        try {
            $argument = new Append($path);
        } catch (LogicException $e) {
            return $this;
        }

        $self = clone $this;
        $self->redirection = $argument;

        return $self;
    }

    public function pipe(self $command): self
    {
        $self = clone $this;

        if ($this->redirection) {
            $self->parameters = $this->parameters->add($this->redirection);
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

    public function environment(): MapInterface
    {
        return $this->environment;
    }

    public function hasWorkingDirectory(): bool
    {
        return is_string($this->workingDirectory);
    }

    public function workingDirectory(): string
    {
        return $this->workingDirectory;
    }

    public function hasInput(): bool
    {
        return $this->input instanceof Readable;
    }

    public function input(): Readable
    {
        return $this->input;
    }

    public function toBeRunInBackground(): bool
    {
        return $this->background;
    }

    public function __toString(): string
    {
        $string = $this->executable;

        if ($this->parameters->size() > 0) {
            $string .= ' '.$this->parameters->join(' ');
        }

        if ($this->redirection) {
            $string .= ' '.$this->redirection;
        }

        return $string;
    }
}
