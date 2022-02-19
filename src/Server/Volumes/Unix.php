<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Volumes;

use Innmind\Server\Control\{
    Server\Volumes,
    Server\Processes,
    Server\Command,
    ScriptFailed,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Either,
    SideEffect,
};

final class Unix implements Volumes
{
    private Processes $processes;

    public function __construct(Processes $processes)
    {
        $this->processes = $processes;
    }

    public function mount(Name $name, Path $mountpoint): Either
    {
        if ($this->isOSX()) {
            return $this->run(
                Command::foreground('diskutil')
                    ->withArgument('mount')
                    ->withArgument($name->toString()),
            );
        }

        return $this->run(
            Command::foreground('mount')
                ->withArgument($name->toString())
                ->withArgument($mountpoint->toString()),
        );
    }

    public function unmount(Name $name): Either
    {
        if ($this->isOSX()) {
            return $this->run(
                Command::foreground('diskutil')
                    ->withArgument('unmount')
                    ->withArgument($name->toString()),
            );
        }

        return $this->run(
            Command::foreground('umount')
                ->withArgument($name->toString()),
        );
    }

    /**
     * @return Either<ScriptFailed, SideEffect>
     */
    private function run(Command $command): Either
    {
        $process = $this->processes->execute($command);

        return $process
            ->wait()
            ->leftMap(static fn($e) => new ScriptFailed($command, $process, $e));
    }

    private function isOSX(): bool
    {
        return $this
            ->run(Command::foreground('which diskutil'))
            ->match(
                static fn() => true,
                static fn() => false,
            );
    }
}
