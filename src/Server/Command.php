<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server\Command\Argument,
    Server\Command\Option,
    Exception\EmptyExecutableNotAllowed,
    Exception\EmptyEnvironmentKeyNotAllowed
};
use Innmind\Immutable\{
    Stream,
    Map,
    MapInterface
};

final class Command
{
    private $executable;
    private $parameters;
    private $environment;
    private $workingDirectory;

    public function __construct(string $executable)
    {
        if (empty($executable)) {
            throw new EmptyExecutableNotAllowed;
        }

        $this->executable = $executable;
        $this->parameters = new Stream('object');
        $this->environment = new Map('string', 'string');
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

    public function __toString(): string
    {
        $string = $this->executable;

        if ($this->parameters->size() > 0) {
            $string .= ' '.$this->parameters->join(' ');
        }

        return $string;
    }
}
