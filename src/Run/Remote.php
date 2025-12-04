<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Run;

use Innmind\Server\Control\Server\Command;
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
    private function __construct(
        private Implementation $run,
        private User $user,
        private Host $host,
        private ?Port $port,
    ) {
    }

    #[\Override]
    public function __invoke(Command|Command\OverSsh $command): Attempt
    {
        return ($this->run)(Command\OverSsh::of(
            $this->user,
            $this->host,
            $this->port,
            $command,
        ));
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
