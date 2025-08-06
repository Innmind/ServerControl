<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers\Mock;

use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

/**
 * @internal
 */
final class Reboot
{
    private function __construct(
        private bool $success,
    ) {
    }

    public static function success(): self
    {
        return new self(true);
    }

    public static function fail(): self
    {
        return new self(false);
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function run(): Attempt
    {
        return match ($this->success) {
            true => Attempt::result(SideEffect::identity()),
            false => Attempt::error(new \Exception('Failed to reboot')),
        };
    }
}
