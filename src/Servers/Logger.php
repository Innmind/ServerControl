<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Processes\LoggerProcesses
};
use Psr\Log\LoggerInterface;

final class Logger implements Server
{
    private $processes;

    public function __construct(
        Server $server,
        LoggerInterface $logger
    ) {
        $this->processes = new LoggerProcesses(
            $server->processes(),
            $logger
        );
    }

    public function processes(): Processes
    {
        return $this->processes;
    }
}
