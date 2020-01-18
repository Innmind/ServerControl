<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Volumes;

use Innmind\Server\Control\{
    Server\Volumes,
    Server\Processes,
    Server\Command,
    Exception\ScriptFailed,
};
use Innmind\Url\Path;

final class Unix implements Volumes
{
    private Processes $processes;

    public function __construct(Processes $processes)
    {
        $this->processes = $processes;
    }

    public function mount(Name $name, Path $mountpoint): void
    {
        if ($this->isOSX()) {
            $this->run(
                Command::foreground('diskutil')
                    ->withArgument('mount')
                    ->withArgument($name->toString()),
            );

            return;
        }

        $this->run(
            Command::foreground('mount')
                ->withArgument($name->toString())
                ->withArgument($mountpoint->toString()),
        );
    }

    public function unmount(Name $name): void
    {
        if ($this->isOSX()) {
            $this->run(
                Command::foreground('diskutil')
                    ->withArgument('unmount')
                    ->withArgument($name->toString()),
            );

            return;
        }

        $this->run(
            Command::foreground('umount')
                ->withArgument($name->toString()),
        );
    }

    private function run(Command $command): void
    {
        $process = $this->processes->execute($command);
        $process->wait();

        if (!$process->exitCode()->isSuccessful()) {
            throw new ScriptFailed($command, $process);
        }
    }

    private function isOSX(): bool
    {
        $isOSX = $this->processes->execute(Command::foreground('which diskutil'));
        $isOSX->wait();

        return $isOSX->exitCode()->isSuccessful();
    }
}
