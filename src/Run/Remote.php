<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Run;

use Innmind\Server\Control\{
    Server\Command,
};
use Innmind\Url\Authority\{
    Host,
    Port,
    UserInformation\User,
};
use Innmind\Immutable\Attempt;

/**
 * @internal
 */
final class Remote implements Implementation
{
    private Implementation $run;
    private Command $command;

    private function __construct(
        Implementation $run,
        User $user,
        Host $host,
        ?Port $port = null,
    ) {
        $this->run = $run;
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
    public function __invoke(Command $command): Attempt
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

        return ($this->run)(
            $this->command->withArgument($command->toString()),
        );
    }

    /**
     * @internal
     */
    public static function of(
        Implementation $run,
        User $user,
        Host $host,
        ?Port $port = null,
    ): self {
        return new self(
            $run,
            $user,
            $host,
            $port,
        );
    }
}
