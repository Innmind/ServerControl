<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Volumes,
};
use Innmind\Immutable\Attempt;
use Innmind\BlackBox\Runner\Assert;

final class Mock implements Server
{
    private function __construct(
        private Assert $assert,
        private Mock\Actions $actions,
    ) {
    }

    public static function new(Assert $assert): self
    {
        return new self($assert, Mock\Actions::new($assert));
    }

    #[\Override]
    public function processes(): Processes
    {
    }

    #[\Override]
    public function volumes(): Volumes
    {
        return Mock\Volumes::new($this->assert, $this->actions);
    }

    #[\Override]
    public function reboot(): Attempt
    {
        return $this
            ->actions
            ->pull(Mock\Reboot::class, 'No reboot was expected')
            ->run();
    }

    #[\Override]
    public function shutdown(): Attempt
    {
        return $this
            ->actions
            ->pull(Mock\Shutdown::class, 'No shutdown was expected')
            ->run();
    }

    #[\NoDiscard]
    public function willReboot(): self
    {
        return new self(
            $this->assert,
            $this->actions->add(Mock\Reboot::success()),
        );
    }

    #[\NoDiscard]
    public function willFailToReboot(): self
    {
        return new self(
            $this->assert,
            $this->actions->add(Mock\Reboot::fail()),
        );
    }

    #[\NoDiscard]
    public function willShutdown(): self
    {
        return new self(
            $this->assert,
            $this->actions->add(Mock\Shutdown::success()),
        );
    }

    #[\NoDiscard]
    public function willFailToShutdown(): self
    {
        return new self(
            $this->assert,
            $this->actions->add(Mock\Shutdown::fail()),
        );
    }

    #[\NoDiscard]
    public function willMountVolume(string $name, string $path): self
    {
        return new self(
            $this->assert,
            $this->actions->add(Mock\MountVolume::success($name, $path)),
        );
    }

    #[\NoDiscard]
    public function willFailToMountVolume(string $name, string $path): self
    {
        return new self(
            $this->assert,
            $this->actions->add(Mock\MountVolume::fail($name, $path)),
        );
    }

    #[\NoDiscard]
    public function willUnmountVolume(string $name): self
    {
        return new self(
            $this->assert,
            $this->actions->add(Mock\UnmountVolume::success($name)),
        );
    }

    #[\NoDiscard]
    public function willFailToUnmountVolume(string $name): self
    {
        return new self(
            $this->assert,
            $this->actions->add(Mock\UnmountVolume::fail($name)),
        );
    }

    public function assert(): void
    {
        $this->assert->count(
            0,
            $this->actions,
            'There are untriggered actions',
        );
    }
}
