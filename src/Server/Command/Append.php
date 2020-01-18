<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Command;

use Innmind\Url\Path;

final class Append
{
    private string $value;

    public function __construct(Path $path)
    {
        $this->value = '>> '.(new Argument($path->toString()))->toString();
    }

    public function toString(): string
    {
        return $this->value;
    }
}
