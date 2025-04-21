<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server\Process\Pid,
    ScriptFailed,
};
use Innmind\Immutable\{
    Either,
    Attempt,
    SideEffect,
};

interface Processes
{
    /**
     * @return Attempt<Process>
     */
    public function execute(Command $command): Attempt;

    /**
     * @return Either<ScriptFailed, SideEffect>
     */
    public function kill(Pid $pid, Signal $signal): Either;
}
