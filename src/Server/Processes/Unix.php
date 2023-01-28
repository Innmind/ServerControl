<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Processes;

use Innmind\Server\Control\{
    Server\Processes,
    Server\Command,
    Server\Process,
    Server\Process\Pid,
    Server\Process\Foreground,
    Server\Process\Background,
    Server\Signal,
    ScriptFailed,
};
use Innmind\TimeContinuum\{
    Clock,
    ElapsedPeriod,
    Period,
    Earth,
    Earth\Period\Second,
};
use Innmind\TimeWarp\Halt;
use Innmind\Stream\{
    Watch,
    Capabilities,
    Streams,
};
use Innmind\Immutable\Either;

final class Unix implements Processes
{
    private Clock $clock;
    private Watch $watch;
    private Halt $halt;
    private Capabilities $capabilities;
    private Period $grace;

    private function __construct(
        Clock $clock,
        Watch $watch,
        Halt $halt,
        Capabilities $capabilities,
        Period $grace,
    ) {
        $this->clock = $clock;
        $this->watch = $watch;
        $this->halt = $halt;
        $this->capabilities = $capabilities;
        $this->grace = $grace;
    }

    /**
     * @param callable(ElapsedPeriod): Watch $watch
     */
    public static function of(
        Clock $clock,
        callable $watch,
        Halt $halt,
        Period $grace = null,
        Capabilities $capabilities = null,
    ): self {
        // we do not use a timeout when watching for stream otherwise we would
        // wait when writing each chunk of input to the process stream
        return new self(
            $clock,
            $watch(new Earth\ElapsedPeriod(0)),
            $halt,
            $capabilities ?? Streams::fromAmbientAuthority(),
            $grace ?? new Second(1),
        );
    }

    public function execute(Command $command): Process
    {
        $process = new Process\Unix(
            $this->clock,
            $this->watch,
            $this->halt,
            $this->capabilities,
            $this->grace,
            $command,
        );

        if ($command->toBeRunInBackground()) {
            return new Background($process());
        }

        return new Foreground($process(), $command->outputToBeStreamed());
    }

    public function kill(Pid $pid, Signal $signal): Either
    {
        $process = $this->execute(
            $command = Command::foreground('kill')
                ->withShortOption($signal->toString())
                ->withArgument($pid->toString()),
        );

        return $process
            ->wait()
            ->leftMap(static fn($e) => new ScriptFailed($command, $process, $e));
    }
}
