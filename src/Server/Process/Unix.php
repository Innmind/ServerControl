<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Server\Command;
use Innmind\TimeContinuum\{
    Clock,
    Period,
};
use Innmind\Filesystem\File\Content;
use Innmind\TimeWarp\Halt;
use Innmind\Stream\Watch;
use Innmind\Immutable\Maybe;

/**
 * @internal
 */
final class Unix
{
    private Clock $clock;
    private Watch $watch;
    private Halt $halt;
    private Period $grace;
    private Command $command;

    public function __construct(
        Clock $clock,
        Watch $watch,
        Halt $halt,
        Period $grace,
        Command $command,
    ) {
        $this->clock = $clock;
        $this->watch = $watch;
        $this->halt = $halt;
        $this->grace = $grace;
        $this->command = $command;
    }

    public function __invoke(): StartedProcess
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
                // todo return a Maybe or Either instead
                throw new \RuntimeException;
            }

            return [$process, $pipes];
        };

        // todo use a named constructor to allow to return a Maybe or Either
        // when starting the process fails
        return new StartedProcess(
            $this->clock,
            $this->watch,
            $this->halt,
            $this->grace,
            $start,
            $this->command->timeout(),
            $this->input(),
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

    /**
     * @return Maybe<Content>
     */
    private function input(): Maybe
    {
        /** @var Maybe<Content> */
        return $this
            ->command
            ->input()
            ->map(static fn($stream) => Content\OfStream::of($stream));
    }
}
