<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Processes\RemoteProcesses,
};
use Innmind\Url\Authority\{
    Host,
    Port,
    UserInformation\User,
};

final class Remote implements Server
{
    private Processes $processes;

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
    }

    public function processes(): Processes
    {
        return $this->processes;
    }
}
