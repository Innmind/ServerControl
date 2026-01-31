<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Run;

use Innmind\Server\Control\Server\{
    Command,
    Process,
};
use Innmind\Time\{
    Clock,
    Period,
    Halt,
};
use Innmind\IO\IO;
use Innmind\Immutable\Attempt;

/**
 * @internal
 */
final class Unix implements Implementation
{
    private function __construct(
        private Clock $clock,
        private IO $io,
        private Halt $halt,
        private Period $grace,
    ) {
    }

    #[\Override]
    public function __invoke(Command|Command\OverSsh $command): Attempt
    {
        if ($command instanceof Command\OverSsh) {
            $command = $command->normalize();
        }

        return Attempt::of(function() use ($command) {
            $process = new Process\Unix(
                $this->clock,
                $this->io,
                $this->halt,
                $this->grace,
                $command,
            );

            if ($command->toBeRunInBackground()) {
                return Process::background($process());
            }

            return Process::foreground($process(), $command->outputToBeStreamed());
        });
    }

    /**
     * @internal
     */
    public static function of(
        Clock $clock,
        IO $io,
        Halt $halt,
        ?Period $grace = null,
    ): self {
        return new self(
            $clock,
            $io,
            $halt,
            $grace ?? Period::second(1),
        );
    }
}
