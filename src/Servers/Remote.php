<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Volumes,
};
use Innmind\Url\Authority\{
    Host,
    Port,
    UserInformation\User,
};
use Innmind\Immutable\Either;

final class Remote implements Server
{
    private Processes $processes;
    private Volumes $volumes;

    public function __construct(
        Server $server,
        User $user,
        Host $host,
        ?Port $port = null,
    ) {
        $this->processes = new Processes\Remote(
            $server->processes(),
            $user,
            $host,
            $port,
        );
        $this->volumes = new Volumes\Unix($this->processes);
    }

    #[\Override]
    public function processes(): Processes
    {
        return $this->processes;
    }

    #[\Override]
    public function volumes(): Volumes
    {
        return $this->volumes;
    }

    #[\Override]
    public function reboot(): Either
    {
        return Server\Script::of('sudo shutdown -r now')($this);
    }

    #[\Override]
    public function shutdown(): Either
    {
        return Server\Script::of('sudo shutdown -h now')($this);
    }
}
