<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\{
    Server\Process as ProcessInterface,
    Server\Process\Output\StaticOutput,
    Server\Process\Output\Type,
    Exception\BackgroundProcessInformationNotAvailable
};
use Innmind\Immutable\{
    Map,
    Str
};
use Symfony\Component\Process\Process;

final class BackgroundProcess implements ProcessInterface
{
    private $output;

    public function __construct(Process $process)
    {
        //read process pipes once otherwise the process will be killed
        $process->getIterator()->next();
        $this->output = new StaticOutput(
            new Map(Str::class, Type::class)
        );
    }

    public function pid(): Pid
    {
        throw new BackgroundProcessInformationNotAvailable;
    }

    public function output(): Output
    {
        return $this->output;
    }

    /**
     * {@inheritdoc}
     */
    public function exitCode(): ExitCode
    {
        throw new BackgroundProcessInformationNotAvailable;
    }

    public function wait(): ProcessInterface
    {
        return $this;
    }

    public function isRunning(): bool
    {
        throw new BackgroundProcessInformationNotAvailable;
    }
}
