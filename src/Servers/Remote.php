<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Processes\RemoteProcesses,
    Server\Volumes,
};
use Innmind\Url\Authority\{
    Host,
    Port,
    UserInformation\User,
};

final class Remote implements Server
{
    private Processes $processes;
    private Volumes $volumes;

    public function __construct(
        Server $server,
        User $user,
        Host $host,
        Port $port = null
    ) {
        $this->processes = new RemoteProcesses(
            $server->processes(),
            $user,
            $host,
            $port,
        );
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
