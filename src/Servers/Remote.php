<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Processes\RemoteProcesses,
};
use Innmind\Url\Authority\{
    HostInterface,
    PortInterface,
    UserInformation\UserInterface,
};

final class Remote implements Server
{
    private Processes $processes;

    public function __construct(
        Server $server,
        UserInterface $user,
        HostInterface $host,
        PortInterface $port = null
    ) {
        $this->processes = new RemoteProcesses(
            $server->processes(),
            $user,
            $host,
            $port
        );
    }

    public function processes(): Processes
    {
        return $this->processes;
    }
}
