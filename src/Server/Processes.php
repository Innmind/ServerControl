<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server\Process\Pid,
    ScriptFailed,
};
use Innmind\Immutable\{
    Either,
    SideEffect,
};

interface Processes
{
    public function execute(Command $command): Process;

    /**
     * @return Either<ScriptFailed, SideEffect>
     */
    public function kill(Pid $pid, Signal $signal): Either;
}
