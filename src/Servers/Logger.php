<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Server\Processes,
    Server\Volumes,
};
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
final class Logger implements Implementation
{
    private Processes $processes;
    private Volumes $volumes;

    private function __construct(
        Implementation $server,
        LoggerInterface $logger,
    ) {
        $this->processes = Processes\Logger::psr(
            $server->processes(),
            $logger,
        );
        $this->volumes = new Volumes\Unix($this->processes);
    }

    public static function psr(Implementation $server, LoggerInterface $logger): self
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
}
