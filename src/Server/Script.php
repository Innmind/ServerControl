<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server,
    Exception\ScriptFailed
};
use Innmind\Immutable\Sequence;

final class Script
{
    private $commands;

    public function __construct(Command ...$commands)
    {
        $this->commands = Sequence::of(...$commands);
    }

    public static function of(string ...$commands): self
    {
        foreach ($commands as &$command) {
            $command = Command::foreground($command);
        }

        return new self(...$commands);
    }

    public function __invoke(Server $server): void
    {
        $processes = $server->processes();

        $this->commands->reduce(
            $processes,
            static function(Processes $processes, Command $command): Processes {
                $process = $processes->execute($command);
                $exitCode = $process
                    ->wait()
                    ->exitCode();

                if (!$exitCode->isSuccessful()) {
                    throw new ScriptFailed($command, $process);
                }

                return $processes;
            }
        );
    }
}
