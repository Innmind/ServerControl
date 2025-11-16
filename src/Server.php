<?php
declare(strict_types = 1);

namespace Innmind\Server\Control;

use Innmind\Server\Control\{
    Servers\Implementation,
    Servers\Unix,
    Servers\Remote,
    Servers\Logger,
    Server\Processes,
    Server\Volumes,
    Server\Script,
    Server\Command,
};
use Innmind\TimeWarp\Halt;
use Innmind\IO\IO;
use Innmind\Url\Authority\{
    Host,
    Port,
    UserInformation\User,
};
use Innmind\TimeContinuum\{
    Clock,
    Period,
};
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};
use Psr\Log\LoggerInterface;

final class Server
{
    private function __construct(
        private Implementation $implementation,
    ) {
    }

    /**
     * @internal Use the factory instead
     */
    public static function new(
        Clock $clock,
        IO $io,
        Halt $halt,
        ?Period $grace = null,
    ): self {
        return new self(Unix::of(
            $clock,
            $io,
            $halt,
            $grace,
        ));
    }

    public static function remote(
        self $server,
        User $user,
        Host $host,
        ?Port $port = null,
    ): self {
        return new self(Remote::of(
            $server->implementation,
            $user,
            $host,
            $port,
        ));
    }

    public static function logger(self $server, LoggerInterface $logger): self
    {
        return new self(Logger::psr(
            $server->implementation,
            $logger,
        ));
    }

    #[\NoDiscard]
    public function processes(): Processes
    {
        return $this->implementation->processes();
    }

    #[\NoDiscard]
    public function volumes(): Volumes
    {
        return Volumes::of($this->processes());
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function reboot(): Attempt
    {
        return Script::of(Command::foreground('sudo shutdown -r now'))($this);
    }

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function shutdown(): Attempt
    {
        return Script::of(Command::foreground('sudo shutdown -h now'))($this);
    }
}
