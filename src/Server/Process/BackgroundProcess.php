<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\{
    Server\Process as ProcessInterface,
    Exception\BackgroundProcessInformationNotAvailable,
};
use Innmind\Immutable\{
    Sequence,
    Str,
};
use Symfony\Component\Process\Process;

final class BackgroundProcess implements ProcessInterface
{
    private Output $output;

    public function __construct(Process $process)
    {
        //read process pipes once otherwise the process will be killed
        $process->getIterator()->next();
        /** @var Sequence<array{0: Str, 1: Output\Type}> */
        $output = Sequence::of('array');
        $this->output = new Output\Output($output);
    }

    public function pid(): Pid
    {
        throw new BackgroundProcessInformationNotAvailable;
    }

    public function output(): Output
    {
        return $this->output;
    }

    public function exitCode(): ExitCode
    {
        throw new BackgroundProcessInformationNotAvailable;
    }

    public function wait(): void
    {
        // nothing to do
    }

    public function isRunning(): bool
    {
        throw new BackgroundProcessInformationNotAvailable;
    }
}
