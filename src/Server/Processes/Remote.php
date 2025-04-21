<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Processes;

use Innmind\Server\Control\{
    Server\Processes,
    Server\Command,
    Server\Signal,
    Server\Process\Pid,
    ScriptFailed,
};
use Innmind\Url\Authority\{
    Host,
    Port,
    UserInformation\User,
};
use Innmind\Immutable\{
    Either,
    Attempt,
    SideEffect,
};

final class Remote implements Processes
{
    private Processes $processes;
    private Command $command;

    public function __construct(
        Processes $processes,
        User $user,
        Host $host,
        ?Port $port = null,
    ) {
        $this->processes = $processes;
        $command = Command::foreground('ssh');

        if ($port instanceof Port) {
            $command = $command->withShortOption('p', $port->toString());
        }

        $this->command = $command->withArgument(\sprintf(
            '%s@%s',
            $user->toString(),
            $host->toString(),
        ));
    }

    #[\Override]
    public function execute(Command $command): Attempt
    {
        /** @psalm-suppress ArgumentTypeCoercion Due psalm not understing that $bash cannot be empty */
        $command = $command
            ->workingDirectory()
            ->map(static fn($path) => \sprintf(
                'cd %s && %s',
                $path->toString(),
                $command->toString(),
            ))
            ->match(
                static fn($bash) => Command::foreground($bash),
                static fn() => $command,
            );

        return $this
            ->processes
            ->execute(
                $this->command->withArgument($command->toString()),
            );
    }

    #[\Override]
    public function kill(Pid $pid, Signal $signal): Either
    {
        $process = $this->execute(
            $command = Command::foreground('kill')
                ->withShortOption($signal->toString())
                ->withArgument($pid->toString()),
        )->unwrap();

        return $process
            ->wait()
            ->map(static fn() => new SideEffect)
            ->leftMap(static fn($e) => new ScriptFailed($command, $process, $e));
    }
}
