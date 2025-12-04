<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Command;

use Innmind\TimeContinuum\Period;
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
interface Implementation
{
    #[\NoDiscard]
    public function withArgument(string $value): self;

    /**
     * @param non-empty-string $key
     */
    #[\NoDiscard]
    public function withOption(string $key, ?string $value = null): self;

    /**
     * @param non-empty-string $key
     */
    #[\NoDiscard]
    public function withShortOption(string $key, ?string $value = null): self;

    /**
     * @param non-empty-string $key
     */
    #[\NoDiscard]
    public function withEnvironment(string $key, string $value): self;
    #[\NoDiscard]
    public function withWorkingDirectory(Path $path): self;
    #[\NoDiscard]
    public function withInput(Content $input): self;
    #[\NoDiscard]
    public function overwrite(Path $path): self;
    #[\NoDiscard]
    public function append(Path $path): self;
    #[\NoDiscard]
    public function timeoutAfter(Period $timeout): self;
    #[\NoDiscard]
    public function streamOutput(): self;

    /**
     * @return Map<string, string>
     */
    #[\NoDiscard]
    public function environment(): Map;

    /**
     * @return Maybe<Path>
     */
    #[\NoDiscard]
    public function workingDirectory(): Maybe;

    /**
     * @return Maybe<Content>
     */
    #[\NoDiscard]
    public function input(): Maybe;
    #[\NoDiscard]
    public function toBeRunInBackground(): bool;

    /**
     * @return Maybe<Period>
     */
    #[\NoDiscard]
    public function timeout(): Maybe;
    #[\NoDiscard]
    public function outputToBeStreamed(): bool;

    /**
     * @return non-empty-string
     */
    #[\NoDiscard]
    public function toString(): string;
}
