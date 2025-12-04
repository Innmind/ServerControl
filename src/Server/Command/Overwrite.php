<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Command;

use Innmind\Url\Path;

/**
 * @psalm-immutable
 * @internal
 */
final class Overwrite
{
    public function __construct(private Path $path)
    {
    }

    public function path(): Path
    {
        return $this->path;
    }

    public function toString(): string
    {
        return '> '.(new Argument($this->path->toString()))->toString();
    }
}
