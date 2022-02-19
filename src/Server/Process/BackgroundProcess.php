<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Server\Process as ProcessInterface;
use Innmind\Immutable\{
    Sequence,
    Str,
    Maybe,
    Either,
    SideEffect,
};

final class BackgroundProcess implements ProcessInterface
{
    private Output $output;

    public function __construct(StartedProcess $process)
    {
        // wait for the process to be started in the background otherwise the
        // process will be killed
        // this also allows to send any input to the stream
        $process->wait();
        /** @var Sequence<array{0: Str, 1: Output\Type}> */
        $output = Sequence::of();
        $this->output = new Output\Output($output);

        // the pid returned by `$process->pid()` is the one for the "foreground"
        // process that starts the background one, the real pid (the one we
        // expect to be usable) will generally be `$process->pid() + 1` and is
        // displayed in the output of the "foreground" process but there is no
        // garanty for that as a new process could be started at the same moment
        // and so the process might be `+ 2`. for this reason we do not expose
        // the background pid
    }

    public function pid(): Maybe
    {
        /** @var Maybe<Pid> */
        return Maybe::nothing();
    }

    public function output(): Output
    {
        return $this->output;
    }

    public function wait(): Either
    {
        return Either::right(new SideEffect);
    }
}
