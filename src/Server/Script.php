<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server,
    Exception\ProcessFailed,
};
use Innmind\Immutable\{
    Sequence,
    Attempt,
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
     * @return Attempt<SideEffect>
     */
    public function __invoke(Server $server): Attempt
    {
        $processes = $server->processes();

        return $this
            ->commands
            ->sink(SideEffect::identity())
            ->attempt(
                static fn($_, $command) => $processes
                    ->execute($command)
                    ->flatMap(static fn($process) => $process->wait()->match(
                        static fn() => Attempt::result(SideEffect::identity()),
                        static fn($e) => Attempt::error(new ProcessFailed($e)),
                    )),
            );
    }

    /**
     * @no-named-arguments
     * @param non-empty-string $commands
     */
    public static function of(string ...$commands): self
    {
        return new self(...\array_map(
            static fn(string $command): Command => Command::foreground($command),
            $commands,
        ));
    }
}
