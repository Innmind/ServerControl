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
use Innmind\TimeContinuum\Earth\{
    Clock,
    ElapsedPeriod,
    Period\Second,
};
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\Stream\Watch\Select;
use Innmind\Immutable\Either;

final class UnixProcesses implements Processes
{
    public function execute(Command $command): Process
    {
        $process = new Unix(
            new Clock,
            Select::timeoutAfter(new ElapsedPeriod(0)),
            new Usleep,
            new Second(1),
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
