<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Processes;

use Innmind\Server\Control\{
    Server\Processes,
    Server\Command,
    Server\Process,
    Server\Process\Pid,
    Server\Signal,
    Exception\ProcessFailed,
};
use Innmind\TimeContinuum\{
    Clock,
    Period,
};
use Innmind\TimeWarp\Halt;
use Innmind\IO\IO;
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

final class Unix implements Processes
{
    private function __construct(
        private Clock $clock,
        private IO $io,
        private Halt $halt,
        private Period $grace,
    ) {
    }

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

    #[\Override]
    public function execute(Command $command): Attempt
    {
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

    #[\Override]
    public function kill(Pid $pid, Signal $signal): Attempt
    {
        return $this
            ->execute(
                $command = Command::foreground('kill')
                    ->withShortOption($signal->toString())
                    ->withArgument($pid->toString()),
            )
            ->flatMap(static fn($process) => $process->wait()->match(
                static fn() => Attempt::result(SideEffect::identity()),
                static fn($e) => Attempt::error(new ProcessFailed(
                    $command,
                    $process,
                    $e,
                )),
            ));
    }
}
