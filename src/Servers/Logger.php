<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Volumes,
    Server\Command,
};
use Innmind\Immutable\Attempt;
use Psr\Log\LoggerInterface;

final class Logger implements Server
{
    private Processes $processes;
    private Volumes $volumes;

    private function __construct(
        Server $server,
        LoggerInterface $logger,
    ) {
        $this->processes = Processes\Logger::psr(
            $server->processes(),
            $logger,
        );
        $this->volumes = new Volumes\Unix($this->processes);
    }

    public static function psr(Server $server, LoggerInterface $logger): self
    {
        return new self($server, $logger);
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
