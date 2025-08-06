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
final class MountVolume
{
    private function __construct(
        private bool $success,
        private string $name,
        private string $path,
    ) {
    }

    public static function success(string $name, string $path): self
    {
        return new self(true, $name, $path);
    }

    public static function fail(string $name, string $path): self
    {
        return new self(false, $name, $path);
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function run(
        Assert $assert,
        string $name,
        string $path,
    ): Attempt {
        $assert->same($this->name, $name);
        $assert->same($this->path, $path);

        return match ($this->success) {
            true => Attempt::result(SideEffect::identity()),
            false => Attempt::error(new \Exception('Failed to mount volume')),
        };
    }
}
