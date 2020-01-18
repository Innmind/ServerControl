<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Processes\UnixProcesses,
};

final class Unix implements Server
{
    private Processes $processes;

    public function __construct()
    {
        $this->processes = new UnixProcesses;
    }

    public function processes(): Processes
    {
        return $this->processes;
    }
}
