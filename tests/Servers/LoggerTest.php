<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Servers;

use Innmind\Server\Control\{
    Server,
    Server\Processes,
    Server\Command,
    Server\Volumes,
};
use Innmind\TimeContinuum\Clock;
use Innmind\TimeWarp\Halt;
use Innmind\IO\IO;
use Innmind\Immutable\SideEffect;
use Psr\Log\NullLogger;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class LoggerTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testProcesses()
    {
        $server = $this->server('ls');

        $logger = Server::logger(
            $server,
            new NullLogger,
        );

        $this->assertInstanceOf(
            Processes::class,
            $logger->processes(),
        );
        $logger->processes()->execute(Command::foreground('ls'));
    }

    #[Group('ci')]
    #[Group('local')]
    public function testVolumes()
    {
        $server = $this->server('which diskutil', "diskutil 'unmount' '/dev'");

        $logger = Server::logger(
            $server,
            new NullLogger,
        );

        $this->assertInstanceOf(
            Volumes::class,
            $logger->volumes(),
        );
        $logger->volumes()->unmount(Volumes\Name::of('/dev'));
    }

    #[Group('ci')]
    #[Group('local')]
    public function testReboot()
    {
        $server = $this->server('sudo shutdown -r now');

        $logger = Server::logger(
            $server,
            new NullLogger,
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $logger->reboot()->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    public function testShutdown()
    {
        $server = $this->server('sudo shutdown -h now');

        $logger = Server::logger(
            $server,
            new NullLogger,
        );

        $this->assertInstanceOf(
            SideEffect::class,
            $logger->shutdown()->match(
                static fn($sideEffect) => $sideEffect,
                static fn() => null,
            ),
        );
    }

    private function server(string ...$commands): Server
    {
        $processes = Server::new(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Halt::new(),
        )->processes();

        return Server::via(
            function($command) use ($processes, &$commands) {
                $expected = \array_shift($commands);
                $this->assertNotNull($expected);
                $this->assertSame(
                    $expected,
                    $command->toString(),
                );

                return $processes->execute(Command::foreground('echo'));
            },
        );
    }
}
