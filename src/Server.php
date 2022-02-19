<?php
declare(strict_types = 1);

namespace Innmind\Server\Control;

use Innmind\Server\Control\{
    Server\Processes,
    Server\Volumes,
};
use Innmind\Immutable\{
    Either,
    SideEffect,
};

interface Server
{
    public function processes(): Processes;
    public function volumes(): Volumes;

    /**
     * @return Either<ScriptFailed, SideEffect>
     */
    public function reboot(): Either;

    /**
     * @return Either<ScriptFailed, SideEffect>
     */
    public function shutdown(): Either;
}
