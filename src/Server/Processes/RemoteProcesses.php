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
    Host,
    Port,
    UserInformation\User,
};

final class RemoteProcesses implements Processes
{
    private Processes $processes;
    private Command $command;

    public function __construct(
        Processes $processes,
        User $user,
        Host $host,
        Port $port = null
    ) {
        $this->processes = $processes;
        $command = Command::foreground('ssh');

        if ($port instanceof Port) {
            $command = $command->withShortOption('p', $port->toString());
        }

        $this->command = $command->withArgument(sprintf(
            '%s@%s',
            $user->toString(),
            $host->toString(),
        ));
    }

    public function execute(Command $command): Process
    {
        if ($command->hasWorkingDirectory()) {
            $command = Command::foreground(sprintf(
                'cd %s && %s',
                $command->workingDirectory()->toString(),
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
