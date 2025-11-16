<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers;

use Innmind\Server\Control\Server\Processes;
use Innmind\TimeContinuum\{
    Clock,
    Period,
};
use Innmind\TimeWarp\Halt;
use Innmind\IO\IO;

/**
 * @internal
 */
final class Unix implements Implementation
{
    private Processes $processes;

    private function __construct(
        Clock $clock,
        IO $io,
        Halt $halt,
        ?Period $grace = null,
    ) {
        $this->processes = Processes\Unix::of(
            $clock,
            $io,
            $halt,
            $grace,
        );
    }

    /**
     * @internal Use the factory instead
     */
    public static function of(
        Clock $clock,
        IO $io,
        Halt $halt,
        ?Period $grace = null,
    ): self {
        return new self($clock, $io, $halt, $grace);
    }

    #[\Override]
    public function processes(): Processes
    {
        return $this->processes;
    }
}
