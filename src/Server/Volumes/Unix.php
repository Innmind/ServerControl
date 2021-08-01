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
use Innmind\Immutable\Either;

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
        $throwOnError = $process
            ->wait()
            ->flatMap(
                static fn($exit) => $exit
                    ->map(static fn($exit) => $exit->successful())
                    ->match(
                        static fn($successful) => $successful ? Either::right(null) : Either::left(new ScriptFailed(
                            $command,
                            $process,
                        )),
                        static fn() => Either::right(null),
                    ),
            )
            ->match(
                static fn($e) => static fn() => throw $e,
                static fn() => static fn() => null,
            );
        $throwOnError();
    }

    private function isOSX(): bool
    {
        return $this
            ->processes
            ->execute(Command::foreground('which diskutil'))
            ->wait()
            ->map(static fn($exit) => $exit->match(
                static fn($exit) => $exit->successful(),
                static fn() => false,
            ))
            ->match(
                static fn() => false,
                static fn($isOSX) => $isOSX,
            );
    }
}
