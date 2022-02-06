<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server\Volumes\Name,
    ScriptFailed,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Either,
    SideEffect,
};

interface Volumes
{
    /**
     * @return Either<ScriptFailed, SideEffect>
     */
    public function mount(Name $name, Path $mountpoint): Either;

    /**
     * @return Either<ScriptFailed, SideEffect>
     */
    public function unmount(Name $name): Either;
}
