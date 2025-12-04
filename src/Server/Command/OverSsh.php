<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Command;

use Innmind\Server\Control\Server\Command;
use Innmind\Url\Authority\{
    Host,
    Port,
    UserInformation\User,
};

/**
 * @psalm-immutable
 * @internal
 */
final class OverSsh
{
    private function __construct(
        private User $user,
        private Host $host,
        private ?Port $port,
        private Command|self $command,
    ) {
    }

    /**
     * @psalm-pure
     * @internal
     */
    #[\NoDiscard]
    public static function of(
        User $user,
        Host $host,
        ?Port $port,
        Command|self $command,
    ): self {
        return new self($user, $host, $port, $command);
    }

    #[\NoDiscard]
    public function user(): User
    {
        return $this->user;
    }

    #[\NoDiscard]
    public function host(): Host
    {
        return $this->host;
    }

    #[\NoDiscard]
    public function port(): ?Port
    {
        return $this->port;
    }

    #[\NoDiscard]
    public function command(): Command|self
    {
        return $this->command;
    }

    #[\NoDiscard]
    public function normalize(): Command
    {
        $command = $this->command;

        if ($command instanceof self) {
            $command = $command->normalize();
        }

        $ssh = Command::foreground('ssh');

        if ($this->port instanceof Port) {
            $ssh = $ssh->withShortOption('p', $this->port->toString());
        }

        $ssh = $ssh->withArgument(\sprintf(
            '%s@%s',
            $this->user->toString(),
            $this->host->toString(),
        ));

        $self = $command
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

        return $ssh->withArgument($self->toString());
    }

    /**
     * @return non-empty-string
     */
    #[\NoDiscard]
    public function toString(): string
    {
        return $this->normalize()->toString();
    }
}
