<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Run;

use Innmind\Server\Control\Server\{
    Command,
    Process,
};
use Innmind\Immutable\Attempt;

/**
 * @internal
 */
final class Via implements Implementation
{
    /**
     * @param \Closure(Command): Attempt<Process> $run
     */
    private function __construct(
        private \Closure $run,
    ) {
    }

    #[\Override]
    public function __invoke(Command $command): Attempt
    {
        return ($this->run)($command);
    }

    /**
     * @internal
     *
     * @param callable(Command): Attempt<Process> $run
     */
    public static function of(callable $run): self
    {
        return new self(\Closure::fromCallable($run));
    }
}
