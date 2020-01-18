<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Processes;

use Innmind\Server\Control\Server\{
    Processes,
    Command,
    Signal,
    Process,
    Process\Pid,
};
use Innmind\Url\Authority\{
    HostInterface,
    PortInterface,
    UserInformation\UserInterface,
};

final class RemoteProcesses implements Processes
{
    private Processes $processes;
    private Command $command;

    public function __construct(
        Processes $processes,
        UserInterface $user,
        HostInterface $host,
        PortInterface $port = null
    ) {
        $this->processes = $processes;
        $command = Command::foreground('ssh');

        if ($port instanceof PortInterface) {
            $command = $command->withShortOption('p', (string) $port);
        }

        $this->command = $command->withArgument(sprintf(
            '%s@%s',
            $user,
            $host
        ));
    }

    public function execute(Command $command): Process
    {
        if ($command->hasWorkingDirectory()) {
            $command = Command::foreground(sprintf(
                'cd %s && %s',
                $command->workingDirectory(),
                $command->toString(),
            ));
        }

        return $this
            ->processes
            ->execute(
                $this->command->withArgument($command->toString())
            );
    }

    public function kill(Pid $pid, Signal $signal): Processes
    {
        $this
            ->execute(
                Command::foreground('kill')
                    ->withShortOption($signal->toString())
                    ->withArgument($pid->toString())
            )
            ->wait();

        return $this;
    }
}
