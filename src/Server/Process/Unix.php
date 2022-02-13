<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Server\Command;
use Innmind\Filesystem\File\Content;
use Innmind\Stream\Watch;
use Innmind\Immutable\Maybe;

/**
 * @internal
 */
final class Unix
{
    private Watch $watch;
    private Command $command;

    public function __construct(Watch $watch, Command $command)
    {
        $this->watch = $watch;
        $this->command = $command;
    }

    public function __invoke(): StartedProcess
    {
        $command = $this->command();
        $cwd = $this->workingDirectory();
        $env = $this->env();

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

        // todo handle timeouts

        return new StartedProcess(
            $this->watch,
            $process,
            $pipes,
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
