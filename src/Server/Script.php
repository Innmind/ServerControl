<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server,
    Exception\ScriptFailed,
};
use Innmind\Immutable\{
    Sequence,
    Either,
    SideEffect,
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

    /**
     * @return Either<ScriptFailed, SideEffect>
     */
    public function __invoke(Server $server): Either
    {
        $processes = $server->processes();

        /** @var Either<ScriptFailed, SideEffect> */
        return $this->commands->reduce(
            Either::right(new SideEffect),
            static fn(Either $success, $command) => $success->flatMap(static function() use ($command, $processes) {
                $process = $processes->execute($command);

                return $process
                    ->wait()
                    ->leftMap(static fn($e) => new ScriptFailed($command, $process, $e));
            }),
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
