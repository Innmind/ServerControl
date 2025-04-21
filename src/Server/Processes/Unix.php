<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Processes;

use Innmind\Server\Control\{
    Server\Processes,
    Server\Command,
    Server\Process,
    Server\Process\Pid,
    Server\Signal,
    ScriptFailed,
};
use Innmind\TimeContinuum\{
    Clock,
    Period,
};
use Innmind\TimeWarp\Halt;
use Innmind\IO\IO;
use Innmind\Immutable\{
    Either,
    SideEffect,
};

final class Unix implements Processes
{
    private Clock $clock;
    private Halt $halt;
    private IO $io;
    private Period $grace;

    private function __construct(
        Clock $clock,
        IO $io,
        Halt $halt,
        Period $grace,
    ) {
        $this->clock = $clock;
        $this->io = $io;
        $this->halt = $halt;
        $this->grace = $grace;
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
    public function execute(Command $command): Process
    {
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
    }

    #[\Override]
    public function kill(Pid $pid, Signal $signal): Either
    {
        $process = $this->execute(
            $command = Command::foreground('kill')
                ->withShortOption($signal->toString())
                ->withArgument($pid->toString()),
        );

        return $process
            ->wait()
            ->map(static fn() => new SideEffect)
            ->leftMap(static fn($e) => new ScriptFailed($command, $process, $e));
    }
}
