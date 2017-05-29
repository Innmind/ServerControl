<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Processes;

use Innmind\Server\Control\Server\{
    Processes,
    Command,
    Process,
    Process\Pid,
    Signal,
    Process\Input\Bridge
};
use Symfony\Component\Process\Process as SfProcess;

final class UnixProcesses implements Processes
{
    public function execute(Command $command): Process
    {
        $process = new SfProcess(
            (string) $command,
            $command->hasWorkingDirectory() ?
                $command->workingDirectory() : null,
            $command
                ->environment()
                ->reduce(
                    [],
                    function(array $env, string $key, string $value): array {
                        $env[$key] = $value;

                        return $env;
                    }
                ),
            $command->hasInput() ?
                new Bridge($command->input()) : null
        );
        $process->start();

        return new Process\Process($process);
    }

    public function kill(Pid $pid, Signal $signal): Processes
    {
        $this
            ->execute(
                (new Command('kill'))
                    ->withShortOption((string) $signal)
                    ->withArgument((string) $pid)
            )
            ->wait();

        return $this;
    }
}
