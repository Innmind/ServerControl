<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Processes;

use Innmind\Server\Control\{
    Server,
    Server\Process,
    Server\Command,
    Server\Signal,
    Server\Process\Pid,
};
use Innmind\Time\{
    Clock,
    Halt,
};
use Innmind\IO\IO;
use Innmind\Url\Path;
use Psr\Log\NullLogger;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class LoggerTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testExecute()
    {
        $logger = Server::logger(
            $this->server(),
            new NullLogger,
        )->processes();

        $this->assertInstanceOf(
            Process::class,
            $logger->execute(
                Command::foreground('ls')->withShortOption('l'),
            )->unwrap(),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    public function testExecuteWithWorkingDirectory()
    {
        $logger = Server::logger(
            $this->server(),
            new NullLogger,
        )->processes();

        $this->assertInstanceOf(
            Process::class,
            $logger->execute(
                Command::foreground('ls')
                    ->withShortOption('l')
                    ->withWorkingDirectory(Path::of('/tmp')),
            )->unwrap(),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    public function testKill()
    {
        $logger = Server::logger(
            $this->server(),
            new NullLogger,
        )->processes();

        $this->assertNotNull($logger->kill(new Pid(42), Signal::kill));
    }

    private function server(): Server
    {
        return Server::new(
            Clock::live(),
            IO::fromAmbientAuthority(),
            Halt::new(),
        );
    }
}
