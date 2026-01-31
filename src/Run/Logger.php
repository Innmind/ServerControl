<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Run;

use Innmind\Server\Control\Server\Command;
use Innmind\Immutable\Attempt;
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
final class Logger implements Implementation
{
    private function __construct(
        private Implementation $run,
        private LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public function __invoke(Command|Command\OverSsh $command): Attempt
    {
        $toLog = $command;

        if ($toLog instanceof Command\OverSsh) {
            $toLog = $toLog->normalize();
        }

        $this->logger->info('About to execute the {command}', [
            'command' => $toLog->toString(),
            'workingDirectory' => $toLog->workingDirectory()->match(
                static fn($path) => $path->toString(),
                static fn() => null,
            ),
        ]);

        return ($this->run)($command);
    }

    /**
     * @internal
     */
    public static function psr(Implementation $run, LoggerInterface $logger): self
    {
        return new self($run, $logger);
    }
}
