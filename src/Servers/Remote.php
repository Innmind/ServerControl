<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Server\Processes,
    Server\Volumes,
};
use Innmind\Url\Authority\{
    Host,
    Port,
    UserInformation\User,
};

/**
 * @internal
 */
final class Remote implements Implementation
{
    private Processes $processes;
    private Volumes $volumes;

    private function __construct(
        Implementation $server,
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
        Implementation $server,
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
}
