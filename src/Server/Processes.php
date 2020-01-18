<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\Server\Process\Pid;

interface Processes
{
    public function execute(Command $command): Process;
    public function kill(Pid $pid, Signal $signal): void;
}
