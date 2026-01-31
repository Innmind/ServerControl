<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server\Process\Pid,
    Run,
    Exception\ProcessFailed,
};
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

final class Processes
{
    private function __construct(
        private Run\Implementation $run,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Run\Implementation $run): self
    {
        return new self($run);
    }

    /**
     * @return Attempt<Process>
     */
    #[\NoDiscard]
    public function execute(Command $command): Attempt
    {
        return ($this->run)($command);
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function kill(Pid $pid, Signal $signal): Attempt
    {
        return $this
            ->execute(
                $command = Command::foreground('kill')
                    ->withShortOption($signal->toString())
                    ->withArgument($pid->toString()),
            )
            ->flatMap(
                static fn($process) => $process
                    ->wait()
                    ->attempt(static fn($e) => new ProcessFailed(
                        $command,
                        $process,
                        $e,
                    ))
                    ->map(SideEffect::identity(...)),
            );
    }
}
