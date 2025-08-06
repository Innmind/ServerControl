<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers\Mock;

use Innmind\Immutable\{
    Attempt,
    SideEffect,
};
use Innmind\BlackBox\Runner\Assert;

/**
 * @internal
 */
final class UnmountVolume
{
    private function __construct(
        private bool $success,
        private string $name,
    ) {
    }

    public static function success(string $name): self
    {
        return new self(true, $name);
    }

    public static function fail(string $name): self
    {
        return new self(false, $name);
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function run(
        Assert $assert,
        string $name,
    ): Attempt {
        $assert->same($this->name, $name);

        return match ($this->success) {
            true => Attempt::result(SideEffect::identity()),
            false => Attempt::error(new \Exception('Failed to unmount volume')),
        };
    }
}
