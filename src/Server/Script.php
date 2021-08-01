<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server,
    Exception\ScriptFailed,
    Exception\ProcessTimedOut,
};
use Innmind\Immutable\Sequence;

final class Script
{
    /** @var Sequence<Command> */
    private Sequence $commands;

    /**
     * @no-named-arguments
     */
    public function __construct(Command ...$commands)
    {
        $this->commands = Sequence::of(...$commands);
    }

    public function __invoke(Server $server): void
    {
        $processes = $server->processes();

        $_ = $this->commands->reduce(
            $processes,
            static function(Processes $processes, Command $command): Processes {
                $process = $processes->execute($command);

                try {
                    $process->wait();
                } catch (ProcessTimedOut $e) {
                    throw new ScriptFailed($command, $process, $e);
                }

                $exitCode = $process->exitCode();

                if (!$exitCode->successful()) {
                    throw new ScriptFailed($command, $process);
                }

                return $processes;
            },
        );
    }

    /**
     * @no-named-arguments
     */
    public static function of(string ...$commands): self
    {
        return new self(...\array_map(
            static fn(string $command): Command => Command::foreground($command),
            $commands,
        ));
    }
}
