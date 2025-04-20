<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Server\Process;
use Innmind\Immutable\{
    Sequence,
    Maybe,
    Either,
};

final class Background implements Process
{
    /** @var Sequence<Output\Chunk> */
    private Sequence $output;

    public function __construct(Started $process)
    {
        // wait for the process to be started in the background otherwise the
        // process will be killed
        // this also allows to send any input to the stream
        $process->output()->memoize();
        $this->output = Sequence::of();

        // the pid returned by `$process->pid()` is the one for the "foreground"
        // process that starts the background one, the real pid (the one we
        // expect to be usable) will generally be `$process->pid() + 1` and is
        // displayed in the output of the "foreground" process but there is no
        // garanty for that as a new process could be started at the same moment
        // and so the process might be `+ 2`. for this reason we do not expose
        // the background pid
    }

    #[\Override]
    public function pid(): Maybe
    {
        /** @var Maybe<Pid> */
        return Maybe::nothing();
    }

    #[\Override]
    public function output(): Sequence
    {
        return $this->output;
    }

    #[\Override]
    public function wait(): Either
    {
        return Either::right(new Success($this->output));
    }
}
