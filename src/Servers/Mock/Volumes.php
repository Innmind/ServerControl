<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers\Mock;

use Innmind\Server\Control\Server\{
    Volumes as VolumesInterface,
    Volumes\Name,
};
use Innmind\Url\Path;
use Innmind\Immutable\Attempt;
use Innmind\BlackBox\Runner\Assert;

final class Volumes implements VolumesInterface
{
    private function __construct(
        private Assert $assert,
        private Actions $actions,
    ) {
    }

    /**
     * @internal
     */
    public static function new(Assert $assert, Actions $actions): self
    {
        return new self($assert, $actions);
    }

    #[\Override]
    public function mount(Name $name, Path $mountpoint): Attempt
    {
        return $this
            ->actions
            ->pull(MountVolume::class, 'No volume mounting was expected')
            ->run(
                $this->assert,
                $name->toString(),
                $mountpoint->toString(),
            );
    }

    #[\Override]
    public function unmount(Name $name): Attempt
    {
        return $this
            ->actions
            ->pull(UnmountVolume::class, 'No volume unmounting was expected')
            ->run(
                $this->assert,
                $name->toString(),
            );
    }
}
