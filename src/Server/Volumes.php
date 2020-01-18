<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\Server\Volumes\Name;
use Innmind\Url\Path;

interface Volumes
{
    public function mount(Name $name, Path $mountpoint): void;
    public function unmount(Name $name): void;
}
