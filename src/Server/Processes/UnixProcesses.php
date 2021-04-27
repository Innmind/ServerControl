<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Processes;

use Innmind\Server\Control\Server\{
    Processes,
    Command,
    Process,
    Process\Pid,
    Process\ForegroundProcess,
    Process\BackgroundProcess,
    Signal,
    Process\Input\Bridge,
};
use Symfony\Component\Process\Process as SfProcess;

final class UnixProcesses implements Processes
{
    public function execute(Command $command): Process
    {
        $process = SfProcess::fromShellCommandline(
            $command->toString().($command->toBeRunInBackground() ? ' &' : ''),
            $command->hasWorkingDirectory() ?
                $command->workingDirectory()->toString() : null,
            $command
                ->environment()
                ->reduce(
                    [],
                    static function(array $env, string $key, string $value): array {
                        $env[$key] = $value;

                        return $env;
                    },
                ),
            $command->hasInput() ?
                new Bridge($command->input()) : null,
        );
        $process
            ->setTimeout($command->shouldTimeout() ? $command->timeout()->toInt() : 0)
            ->start();

        if ($command->toBeRunInBackground()) {
            return new BackgroundProcess($process);
        }

        return new ForegroundProcess($process, $command->outputToBeStreamed());
    }

    public function kill(Pid $pid, Signal $signal): void
    {
        $this
            ->execute(
                Command::foreground('kill')
                    ->withShortOption($signal->toString())
                    ->withArgument($pid->toString()),
            )
            ->wait();
    }
}
