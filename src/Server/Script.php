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
    /** @param Sequence<Command> $commands */
    private function __construct(
        private Sequence $commands,
    ) {
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
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
                        static fn($e) => Attempt::error(new ProcessFailed(
                            $command,
                            $process,
                            $e,
                        )),
                    )),
            );
    }

    /**
     * @no-named-arguments
     */
    #[\NoDiscard]
    public static function of(Command ...$commands): self
    {
        return new self(Sequence::of(...$commands));
    }
}
