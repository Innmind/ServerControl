<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\Server\Command\{
    Implementation,
    Definition,
    Pipe,
};
use Innmind\Time\Period;
use Innmind\Filesystem\File\Content;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Map,
    Maybe,
};

/**
 * @psalm-immutable
 */
final class Command
{
    private function __construct(
        private Implementation $implementation,
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
        return new self(Definition::background($executable));
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
        return new self(Definition::foreground($executable));
    }

    #[\NoDiscard]
    public function withArgument(string $value): self
    {
        return new self(
            $this->implementation->withArgument($value),
        );
    }

    /**
     * @param non-empty-string $key
     */
    #[\NoDiscard]
    public function withOption(string $key, ?string $value = null): self
    {
        return new self(
            $this->implementation->withOption($key, $value),
        );
    }

    /**
     * @param non-empty-string $key
     */
    #[\NoDiscard]
    public function withShortOption(string $key, ?string $value = null): self
    {
        return new self(
            $this->implementation->withShortOption($key, $value),
        );
    }

    /**
     * @param non-empty-string $key
     */
    #[\NoDiscard]
    public function withEnvironment(string $key, string $value): self
    {
        return new self(
            $this->implementation->withEnvironment($key, $value),
        );
    }

    /**
     * @param Map<non-empty-string, string> $values
     */
    #[\NoDiscard]
    public function withEnvironments(Map $values): self
    {
        return new self(
            $values->reduce(
                $this->implementation,
                static fn(Implementation $self, $key, $value) => $self->withEnvironment(
                    $key,
                    $value,
                ),
            ),
        );
    }

    #[\NoDiscard]
    public function withWorkingDirectory(Path $path): self
    {
        return new self(
            $this->implementation->withWorkingDirectory($path),
        );
    }

    #[\NoDiscard]
    public function withInput(Content $input): self
    {
        return new self(
            $this->implementation->withInput($input),
        );
    }

    #[\NoDiscard]
    public function overwrite(Path $path): self
    {
        return new self(
            $this->implementation->overwrite($path),
        );
    }

    #[\NoDiscard]
    public function append(Path $path): self
    {
        return new self(
            $this->implementation->append($path),
        );
    }

    #[\NoDiscard]
    public function pipe(self $command): self
    {
        return new self(Pipe::of(
            $this->implementation,
            $command->implementation,
        ));
    }

    #[\NoDiscard]
    public function timeoutAfter(Period $timeout): self
    {
        return new self(
            $this->implementation->timeoutAfter($timeout),
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
            $this->implementation->streamOutput(),
        );
    }

    /**
     * @internal
     *
     * @return Map<string, string>
     */
    public function environment(): Map
    {
        return $this->implementation->environment();
    }

    /**
     * @internal
     *
     * @return Maybe<Path>
     */
    public function workingDirectory(): Maybe
    {
        return $this->implementation->workingDirectory();
    }

    /**
     * @internal
     *
     * @return Maybe<Content>
     */
    public function input(): Maybe
    {
        return $this->implementation->input();
    }

    /**
     * @internal
     */
    public function toBeRunInBackground(): bool
    {
        return $this->implementation->toBeRunInBackground();
    }

    /**
     * @internal
     *
     * @return Maybe<Period>
     */
    public function timeout(): Maybe
    {
        return $this->implementation->timeout();
    }

    /**
     * @internal
     */
    public function outputToBeStreamed(): bool
    {
        return $this->implementation->outputToBeStreamed();
    }

    /**
     * @internal
     *
     * @return non-empty-string
     */
    public function toString(): string
    {
        return $this->implementation->toString();
    }

    /**
     * This method is only to be used by innmind/testing
     *
     * @internal
     */
    public function internal(): Implementation
    {
        return $this->implementation;
    }
}
