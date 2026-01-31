<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Command;

use Innmind\Time\Period;
use Innmind\Filesystem\File\Content;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Map,
    Maybe,
};

/**
 * @psalm-immutable
 * @internal
 */
final class Pipe implements Implementation
{
    private function __construct(
        private Implementation $a,
        private Implementation $b,
    ) {
    }

    /**
     * @psalm-pure
     * @internal
     */
    public static function of(Implementation $a, Implementation $b): self
    {
        return new self($a, $b);
    }

    #[\NoDiscard]
    public function a(): Implementation
    {
        return $this->a;
    }

    #[\NoDiscard]
    public function b(): Implementation
    {
        return $this->b;
    }

    #[\Override]
    #[\NoDiscard]
    public function withArgument(string $value): self
    {
        return new self(
            $this->a,
            $this->b->withArgument($value),
        );
    }

    #[\Override]
    #[\NoDiscard]
    public function withOption(string $key, ?string $value = null): self
    {
        return new self(
            $this->a,
            $this->b->withOption($key, $value),
        );
    }

    #[\Override]
    #[\NoDiscard]
    public function withShortOption(string $key, ?string $value = null): self
    {
        return new self(
            $this->a,
            $this->b->withShortOption($key, $value),
        );
    }

    #[\Override]
    #[\NoDiscard]
    public function withEnvironment(string $key, string $value): self
    {
        return new self(
            $this->a,
            $this->b->withEnvironment($key, $value),
        );
    }

    #[\Override]
    #[\NoDiscard]
    public function withWorkingDirectory(Path $path): self
    {
        return new self(
            $this->a->withWorkingDirectory($path),
            $this->b,
        );
    }

    #[\Override]
    #[\NoDiscard]
    public function withInput(Content $input): self
    {
        return new self(
            $this->a->withInput($input),
            $this->b,
        );
    }

    #[\Override]
    #[\NoDiscard]
    public function overwrite(Path $path): self
    {
        return new self(
            $this->a,
            $this->b->overwrite($path),
        );
    }

    #[\Override]
    #[\NoDiscard]
    public function append(Path $path): self
    {
        return new self(
            $this->a,
            $this->b->append($path),
        );
    }

    #[\Override]
    #[\NoDiscard]
    public function timeoutAfter(Period $timeout): self
    {
        return new self(
            $this->a->timeoutAfter($timeout),
            $this->b,
        );
    }

    #[\Override]
    #[\NoDiscard]
    public function streamOutput(): self
    {
        return new self(
            $this->a->streamOutput(),
            $this->b,
        );
    }

    #[\Override]
    public function environment(): Map
    {
        return $this->a->environment()->merge($this->b->environment());
    }

    #[\Override]
    public function workingDirectory(): Maybe
    {
        return $this->a->workingDirectory();
    }

    #[\Override]
    public function input(): Maybe
    {
        return $this->a->input();
    }

    #[\Override]
    public function toBeRunInBackground(): bool
    {
        // todo should it be 'a || b' ?
        return $this->a->toBeRunInBackground();
    }

    #[\Override]
    public function timeout(): Maybe
    {
        return $this->a->timeout();
    }

    #[\Override]
    public function outputToBeStreamed(): bool
    {
        return $this->a->outputToBeStreamed();
    }

    #[\Override]
    public function toString(): string
    {
        return \sprintf(
            '%s | %s',
            $this->a->toString(),
            $this->b->toString(),
        );
    }
}
