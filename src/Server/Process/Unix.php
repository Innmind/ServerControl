<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\{
    Server\Command,
    Exception\RuntimeException,
};
use Innmind\TimeContinuum\{
    Clock,
    Period,
};
use Innmind\TimeWarp\Halt;
use Innmind\Stream\Capabilities;

/**
 * @internal
 */
final class Unix
{
    private Clock $clock;
    private Halt $halt;
    private Capabilities $capabilities;
    private Period $grace;
    private Command $command;

    public function __construct(
        Clock $clock,
        Capabilities $capabilities,
        Halt $halt,
        Period $grace,
        Command $command,
    ) {
        $this->clock = $clock;
        $this->capabilities = $capabilities;
        $this->halt = $halt;
        $this->grace = $grace;
        $this->command = $command;
    }

    public function __invoke(): Started
    {
        $command = $this->command();
        $cwd = $this->workingDirectory();
        $env = $this->env();

        /** @var callable(): array{0: resource, 1: array{0: resource, 1: resource, 2: resource}} */
        $start = static function() use ($command, $cwd, $env): array {
            $process = @\proc_open(
                $command,
                [
                    ['pipe', 'r'],
                    ['pipe', 'w'],
                    ['pipe', 'w'],
                ],
                $pipes,
                $cwd,
                $env,
            );

            if (!\is_resource($process)) {
                // optimistically this should not happen since we always close
                // all the opened resources meaning we shouldn't reach the limit
                // of opened resources
                // this leaves us with the case where the system itself can't
                // start the process and there is nothing much we could do about
                // it, so the better option is to let the app crash with an
                // exception
                throw new RuntimeException('Failed to start new process');
            }

            return [$process, $pipes];
        };

        return new Started(
            $this->clock,
            $this->halt,
            $this->capabilities,
            $this->grace,
            $start,
            $this->command->toBeRunInBackground(),
            $this->command->timeout(),
            $this->command->input(),
        );
    }

    private function command(): string
    {
        return $this->command->toString().($this->command->toBeRunInBackground() ? ' &' : '');
    }

    private function workingDirectory(): ?string
    {
        return $this->command->workingDirectory()->match(
            static fn($path) => $path->toString(),
            static fn() => null,
        );
    }

    /**
     * @return list<string>
     */
    private function env(): array
    {
        /** @var list<string> */
        return $this
            ->command
            ->environment()
            ->filter(static fn($key) => !\in_array($key, ['argc', 'argv', 'ARGC', 'ARGV'], true))
            ->reduce(
                [],
                static function(array $pairs, $key, $value): array {
                    $pairs[] = "$key=$value";

                    return $pairs;
                },
            );
    }
}
