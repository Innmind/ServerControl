<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Volumes,
    Server\Command,
};
use Innmind\Url\Authority\{
    Host,
    Port,
    UserInformation\User,
};
use Innmind\Immutable\Attempt;

final class Remote implements Server
{
    private Processes $processes;
    private Volumes $volumes;

    private function __construct(
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

    public static function of(
        Server $server,
        User $user,
        Host $host,
        ?Port $port = null,
    ): self {
        return new self($server, $user, $host, $port);
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
    public function reboot(): Attempt
    {
        return Server\Script::of(Command::foreground('sudo shutdown -r now'))($this);
    }

    #[\Override]
    public function shutdown(): Attempt
    {
        return Server\Script::of(Command::foreground('sudo shutdown -h now'))($this);
    }
}
