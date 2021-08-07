<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server,
    Exception\ScriptFailed,
    Exception\ProcessTimedOut,
};
use Innmind\Immutable\{
    Sequence,
    Either,
};

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

                $throwOnError = $process
                    ->wait()
                    ->leftMap(static fn($e) => new ScriptFailed($command, $process, $e))
                    ->match(
                        static fn($e) => static fn() => throw $e,
                        static fn() => static fn() => null,
                    );
                $throwOnError();

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
