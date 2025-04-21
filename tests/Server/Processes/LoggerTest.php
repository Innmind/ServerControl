<?php
declare(strict_types = 1);

namespace Tests\Innmind\Server\Control\Server\Processes;

use Innmind\Server\Control\Server\{
    Processes\Logger,
    Processes\Unix,
    Processes,
    Process,
    Command,
    Signal,
    Process\Pid
};
use Innmind\TimeContinuum\Earth\Clock;
use Innmind\TimeWarp\Halt\Usleep;
use Innmind\Stream\Streams;
use Innmind\Url\Path;
use Psr\Log\NullLogger;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

class LoggerTest extends TestCase
{
    #[Group('ci')]
    #[Group('local')]
    public function testInterface()
    {
        $this->assertInstanceOf(
            Processes::class,
            Logger::psr(
                $this->processes(),
                new NullLogger,
            ),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    public function testExecute()
    {
        $logger = Logger::psr(
            $this->processes(),
            new NullLogger,
        );

        $this->assertInstanceOf(
            Process::class,
            $logger->execute(
                Command::foreground('ls')->withShortOption('l'),
            ),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    public function testExecuteWithWorkingDirectory()
    {
        $logger = Logger::psr(
            $this->processes(),
            new NullLogger,
        );

        $this->assertInstanceOf(
            Process::class,
            $logger->execute(
                Command::foreground('ls')
                    ->withShortOption('l')
                    ->withWorkingDirectory(Path::of('/tmp')),
            ),
        );
    }

    #[Group('ci')]
    #[Group('local')]
    public function testKill()
    {
        $logger = Logger::psr(
            $this->processes(),
            new NullLogger,
        );

        $this->assertNotNull($logger->kill(new Pid(42), Signal::kill));
    }

    private function processes(): Unix
    {
        return Unix::of(
            new Clock,
            Streams::fromAmbientAuthority(),
            new Usleep,
        );
    }
}
