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
            $command->workingDirectory()->match(
                static fn($path) => $path->toString(),
                static fn() => null,
            ),
            $command
                ->environment()
                ->reduce(
                    [],
                    static function(array $env, string $key, string $value): array {
                        $env[$key] = $value;

                        return $env;
                    },
                ),
            $command
                ->input()
                ->map(static fn($input) => new Bridge($input))
                ->match(
                    static fn($input) => $input,
                    static fn() => null,
                ),
        );
        $process
            ->setTimeout($command->timeout()->match(
                static fn($second) => $second->toInt(),
                static fn() => 0,
            ))
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
