<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Processes;

use Innmind\Server\Control\{
    Server\Processes,
    Server\Command,
    Server\Process,
    Server\Process\Pid,
    Server\Process\ForegroundProcess,
    Server\Process\BackgroundProcess,
    Server\Process\Unix,
    Server\Signal,
    ScriptFailed,
};
use Innmind\TimeContinuum\{
    Clock,
    ElapsedPeriod,
    Period,
    Earth,
    Earth\Period\Second,
};
use Innmind\TimeWarp\Halt;
use Innmind\Stream\Watch;
use Innmind\Immutable\Either;

final class UnixProcesses implements Processes
{
    private Clock $clock;
    private Watch $watch;
    private Halt $halt;
    private Period $grace;

    private function __construct(
        Clock $clock,
        Watch $watch,
        Halt $halt,
        Period $grace,
    ) {
        $this->clock = $clock;
        $this->watch = $watch;
        $this->halt = $halt;
        $this->grace = $grace;
    }

    /**
     * @param callable(ElapsedPeriod): Watch $watch
     */
    public static function of(
        Clock $clock,
        callable $watch,
        Halt $halt,
        Period $grace = null,
    ): self {
        // we do not use a timeout when watching for stream otherwise we would
        // wait when writing each chunk of input to the process stream
        return new self(
            $clock,
            $watch(new Earth\ElapsedPeriod(0)),
            $halt,
            $grace ?? new Second(1),
        );
    }

    public function execute(Command $command): Process
    {
        $process = new Unix(
            $this->clock,
            $this->watch,
            $this->halt,
            $this->grace,
            $command,
        );

        if ($command->toBeRunInBackground()) {
            return new BackgroundProcess($process());
        }

        return new ForegroundProcess($process(), $command->outputToBeStreamed());
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
            ->leftMap(static fn($e) => new ScriptFailed($command, $process, $e));
    }
}
