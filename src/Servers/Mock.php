<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Volumes,
};
use Innmind\Immutable\{
    Sequence,
    Attempt,
};
use Innmind\BlackBox\Runner\Assert;

final class Mock implements Server
{
    /**
     * @param Sequence<object> $actions
     */
    private function __construct(
        private Assert $assert,
        private Sequence $actions,
    ) {
    }

    public static function new(Assert $assert): self
    {
        return new self($assert, Sequence::of());
    }

    #[\Override]
    public function processes(): Processes
    {
    }

    #[\Override]
    public function volumes(): Volumes
    {
    }

    #[\Override]
    public function reboot(): Attempt
    {
        $action = $this->actions->first()->match(
            static fn($action) => $action,
            static fn() => null,
        );
        $this->actions = $this->actions->drop(1);

        if ($action instanceof Mock\Reboot) {
            return $action->run();
        }

        $this->assert->fail('No reboot was expected');
    }

    #[\Override]
    public function shutdown(): Attempt
    {
        $action = $this->actions->first()->match(
            static fn($action) => $action,
            static fn() => null,
        );
        $this->actions = $this->actions->drop(1);

        if ($action instanceof Mock\Shutdown) {
            return $action->run();
        }

        $this->assert->fail('No shutdown was expected');
    }

    #[\NoDiscard]
    public function willReboot(): self
    {
        return new self(
            $this->assert,
            ($this->actions)(Mock\Reboot::success()),
        );
    }

    #[\NoDiscard]
    public function willFailToReboot(): self
    {
        return new self(
            $this->assert,
            ($this->actions)(Mock\Reboot::fail()),
        );
    }

    #[\NoDiscard]
    public function willShutdown(): self
    {
        return new self(
            $this->assert,
            ($this->actions)(Mock\Shutdown::success()),
        );
    }

    #[\NoDiscard]
    public function willFailToShutdown(): self
    {
        return new self(
            $this->assert,
            ($this->actions)(Mock\Shutdown::fail()),
        );
    }

    #[\NoDiscard]
    public function willMountVolume(string $name, string $path): self
    {
        return $this;
    }

    #[\NoDiscard]
    public function willFailToMountVolume(string $name, string $path): self
    {
        return $this;
    }

    #[\NoDiscard]
    public function willUnmountVolume(string $name): self
    {
        return $this;
    }

    #[\NoDiscard]
    public function willFailToUnmountVolume(string $name): self
    {
        return $this;
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
