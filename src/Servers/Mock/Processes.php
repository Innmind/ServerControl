<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers\Mock;

use Innmind\Server\Control\Server\{
    Processes as ProcessesInterface,
    Process,
    Process\Pid,
    Command,
    Signal,
};
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

final class Processes implements ProcessesInterface
{
    private function __construct(
        private Actions $actions,
    ) {
    }

    /**
     * @internal
     */
    public static function new(Actions $actions): self
    {
        return new self($actions);
    }

    /**
     * @return Attempt<Process>
     */
    #[\Override]
    public function execute(Command $command): Attempt
    {
        return Attempt::result(
            $this
                ->actions
                ->pull(Execute::class, 'No process expected to be executed')
                ->run($command),
        );
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\Override]
    public function kill(Pid $pid, Signal $signal): Attempt
    {
        return Attempt::result(SideEffect::identity());
    }
}
