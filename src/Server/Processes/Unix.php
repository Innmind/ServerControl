<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Processes;

use Innmind\Server\Control\{
    Server\Processes,
    Server\Command,
    Server\Process,
    Server\Process\Pid,
    Server\Process\Foreground,
    Server\Process\Background,
    Server\Signal,
    ScriptFailed,
};
use Innmind\TimeContinuum\{
    Clock,
    Period,
    Earth\Period\Second,
};
use Innmind\TimeWarp\Halt;
use Innmind\Stream\Capabilities;
use Innmind\Immutable\{
    Either,
    SideEffect,
};

final class Unix implements Processes
{
    private Clock $clock;
    private Halt $halt;
    private Capabilities $capabilities;
    private Period $grace;

    private function __construct(
        Clock $clock,
        Capabilities $capabilities,
        Halt $halt,
        Period $grace,
    ) {
        $this->clock = $clock;
        $this->capabilities = $capabilities;
        $this->halt = $halt;
        $this->grace = $grace;
    }

    public static function of(
        Clock $clock,
        Capabilities $capabilities,
        Halt $halt,
        Period $grace = null,
    ): self {
        return new self(
            $clock,
            $capabilities,
            $halt,
            $grace ?? new Second(1),
        );
    }

    public function execute(Command $command): Process
    {
        $process = new Process\Unix(
            $this->clock,
            $this->capabilities,
            $this->halt,
            $this->grace,
            $command,
        );

        if ($command->toBeRunInBackground()) {
            return new Background($process());
        }

        return new Foreground($process(), $command->outputToBeStreamed());
    }

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
