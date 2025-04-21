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
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Processes::class,
            Logger::psr(
                $this->processes(),
                $this->createMock(LoggerInterface::class),
            ),
        );
    }

    public function testExecute()
    {
        $logger = Logger::psr(
            $this->processes(),
            $log = $this->createMock(LoggerInterface::class),
        );
        $log
            ->expects($this->once())
            ->method('info')
            ->with(
                'About to execute a command',
                [
                    'command' => "ls '-l'",
                    'workingDirectory' => null,
                ],
            );

        $this->assertInstanceOf(
            Process::class,
            $logger->execute(
                Command::foreground('ls')->withShortOption('l'),
            ),
        );
    }

    public function testExecuteWithWorkingDirectory()
    {
        $logger = Logger::psr(
            $this->processes(),
            $log = $this->createMock(LoggerInterface::class),
        );
        $log
            ->expects($this->once())
            ->method('info')
            ->with(
                'About to execute a command',
                [
                    'command' => "ls '-l'",
                    'workingDirectory' => '/tmp',
                ],
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

    public function testKill()
    {
        $logger = Logger::psr(
            $this->processes(),
            $log = $this->createMock(LoggerInterface::class),
        );
        $log
            ->expects($this->once())
            ->method('info')
            ->with(
                'About to kill a process',
                [
                    'pid' => 42,
                    'signal' => 9,
                ],
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
