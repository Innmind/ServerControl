<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\Server\Process\Pid;
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

interface Processes
{
    /**
     * @return Attempt<Process>
     */
    #[\NoDiscard]
    public function execute(Command $command): Attempt;

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function kill(Pid $pid, Signal $signal): Attempt;
}
