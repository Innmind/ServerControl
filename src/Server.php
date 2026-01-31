<?php
declare(strict_types = 1);

namespace Innmind\Server\Control;

use Innmind\Server\Control\{
    Server\Processes,
    Server\Volumes,
    Server\Process,
    Server\Script,
    Server\Command,
};
use Innmind\IO\IO;
use Innmind\Url\Authority\{
    Host,
    Port,
    UserInformation\User,
};
use Innmind\Time\{
    Clock,
    Period,
    Halt,
};
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};
use Psr\Log\LoggerInterface;

final class Server
{
    private function __construct(
        private Run\Implementation $run,
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
        return new self(Run\Unix::of(
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
        return new self(Run\Remote::of(
            $server->run,
            $user,
            $host,
            $port,
        ));
    }

    public static function logger(self $server, LoggerInterface $logger): self
    {
        return new self(Run\Logger::psr(
            $server->run,
            $logger,
        ));
    }

    /**
     * @internal
     *
     * @param callable(Command|Command\OverSsh): Attempt<Process> $run
     */
    public static function via(callable $run): self
    {
        return new self(Run\Via::of($run));
    }

    #[\NoDiscard]
    public function processes(): Processes
    {
        return Processes::of($this->run);
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
