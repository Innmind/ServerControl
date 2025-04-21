<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\Server\Volumes\Name;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

interface Volumes
{
    /**
     * @return Attempt<SideEffect>
     */
    public function mount(Name $name, Path $mountpoint): Attempt;

    /**
     * @return Attempt<SideEffect>
     */
    public function unmount(Name $name): Attempt;
}
