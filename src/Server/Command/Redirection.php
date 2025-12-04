<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Command;

use Innmind\Url\Path;

/**
 * @psalm-immutable
 * @internal
 */
final class Redirection
{
    private function __construct(
        private Path $path,
        private bool $append,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function append(Path $path): self
    {
        return new self($path, true);
    }

    /**
     * @psalm-pure
     */
    public static function overwrite(Path $path): self
    {
        return new self($path, false);
    }

    /**
     * @template R
     *
     * @param callable(Path): R $append
     * @param callable(Path): R $overwrite
     *
     * @return R
     */
    public function match(
        callable $append,
        callable $overwrite,
    ): mixed {
        /** @psalm-suppress ImpureFunctionCall */
        return match ($this->append) {
            true => $append($this->path),
            false => $overwrite($this->path),
        };
    }

    public function toString(): string
    {
        return (match ($this->append) {
            true => '>>',
            false => '>',
        }).' '.(new Str($this->path->toString()))->toString();
    }
}
