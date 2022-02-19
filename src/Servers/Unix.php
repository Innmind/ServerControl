<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Volumes,
};
use Innmind\TimeContinuum\{
    Clock,
    ElapsedPeriod,
    Period,
};
use Innmind\TimeWarp\Halt;
use Innmind\Stream\Watch;
use Innmind\Immutable\Either;

final class Unix implements Server
{
    private Processes $processes;
    private Volumes $volumes;

    /**
     * @param callable(ElapsedPeriod): Watch $watch
     */
    private function __construct(
        Clock $clock,
        callable $watch,
        Halt $halt,
        Period $grace = null,
    ) {
        $this->processes = Processes\Unix::of(
            $clock,
            $watch,
            $halt,
            $grace,
        );
        $this->volumes = new Volumes\Unix($this->processes);
    }

    /**
     * @param callable(ElapsedPeriod): Watch $watch
     */
    public static function of(
        Clock $clock,
        callable $watch,
        Halt $halt,
        Period $grace = null,
    ): self {
        return new self($clock, $watch, $halt, $grace);
    }

    public function processes(): Processes
    {
        return $this->processes;
    }

    public function volumes(): Volumes
    {
        return $this->volumes;
    }

    public function reboot(): Either
    {
        return Server\Script::of('sudo shutdown -r now')($this);
    }

    public function shutdown(): Either
    {
        return Server\Script::of('sudo shutdown -h now')($this);
    }
}
