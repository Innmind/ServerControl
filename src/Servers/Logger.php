<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers;

use Innmind\Server\Control\Server\Processes;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
final class Logger implements Implementation
{
    private Processes $processes;

    private function __construct(
        Implementation $server,
        LoggerInterface $logger,
    ) {
        $this->processes = Processes\Logger::psr(
            $server->processes(),
            $logger,
        );
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
}
