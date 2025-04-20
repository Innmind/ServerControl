<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Command;

use Innmind\Url\Path;

/**
 * @psalm-immutable
 * @internal
 */
final class Append implements Parameter
{
    private string $value;

    public function __construct(Path $path)
    {
        $this->value = '>> '.(new Argument($path->toString()))->toString();
    }

    #[\Override]
    public function toString(): string
    {
        return $this->value;
    }
}
