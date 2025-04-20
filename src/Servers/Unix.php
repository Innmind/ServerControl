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
    Period,
};
use Innmind\TimeWarp\Halt;
use Innmind\Stream\Capabilities;
use Innmind\Immutable\Either;

final class Unix implements Server
{
    private Processes $processes;
    private Volumes $volumes;

    private function __construct(
        Clock $clock,
        Capabilities $capabilities,
        Halt $halt,
        ?Period $grace = null,
    ) {
        $this->processes = Processes\Unix::of(
            $clock,
            $capabilities,
            $halt,
            $grace,
        );
        $this->volumes = new Volumes\Unix($this->processes);
    }

    public static function of(
        Clock $clock,
        Capabilities $capabilities,
        Halt $halt,
        ?Period $grace = null,
    ): self {
        return new self($clock, $capabilities, $halt, $grace);
    }

    #[\Override]
    public function processes(): Processes
    {
        return $this->processes;
    }

    #[\Override]
    public function volumes(): Volumes
    {
        return $this->volumes;
    }

    #[\Override]
    public function reboot(): Either
    {
        return Server\Script::of('sudo shutdown -r now')($this);
    }

    #[\Override]
    public function shutdown(): Either
    {
        return Server\Script::of('sudo shutdown -h now')($this);
    }
}
