<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Volumes,
    Server\Command,
};
use Innmind\TimeContinuum\{
    Clock,
    Period,
};
use Innmind\TimeWarp\Halt;
use Innmind\IO\IO;
use Innmind\Immutable\Attempt;

final class Unix implements Server
{
    private Processes $processes;
    private Volumes $volumes;

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
        $this->volumes = new Volumes\Unix($this->processes);
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

    #[\Override]
    public function volumes(): Volumes
    {
        return $this->volumes;
    }

    #[\Override]
    public function reboot(): Attempt
    {
        return Server\Script::of(Command::foreground('sudo shutdown -r now'))($this);
    }

    #[\Override]
    public function shutdown(): Attempt
    {
        return Server\Script::of(Command::foreground('sudo shutdown -h now'))($this);
    }
}
