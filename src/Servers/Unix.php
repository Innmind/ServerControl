<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Processes\UnixProcesses,
    Server\Volumes,
};

final class Unix implements Server
{
    private Processes $processes;
    private Volumes $volumes;

    public function __construct()
    {
        $this->processes = new UnixProcesses;
        $this->volumes = new Volumes\Unix($this->processes);
    }

    public function processes(): Processes
    {
        return $this->processes;
    }

    public function volumes(): Volumes
    {
        return $this->volumes;
    }
}
