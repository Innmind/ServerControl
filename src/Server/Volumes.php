<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server;

use Innmind\Server\Control\{
    Server\Volumes\Name,
    Exception\ProcessFailed,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

final class Volumes
{
    private function __construct(
        private Processes $processes,
    ) {
    }

    /**
     * @internal
     */
    public static function of(Processes $processes): self
    {
        return new self($processes);
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function mount(Name $name, Path $mountpoint): Attempt
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

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function unmount(Name $name): Attempt
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
     * @return Attempt<SideEffect>
     */
    private function run(Command $command): Attempt
    {
        return $this
            ->processes
            ->execute($command)
            ->flatMap(static fn($process) => $process->wait()->match(
                static fn() => Attempt::result(SideEffect::identity()),
                static fn($e) => Attempt::error(new ProcessFailed(
                    $command,
                    $process,
                    $e,
                )),
            ));
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
