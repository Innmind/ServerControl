<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Server\Process;
use Innmind\Immutable\{
    Sequence,
    Maybe,
    Either,
};

/**
 * @internal
 */
final class Background
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

    /**
     * @return Maybe<Pid>
     */
    public function pid(): Maybe
    {
        /** @var Maybe<Pid> */
        return Maybe::nothing();
    }

    /**
     * @return Sequence<Output\Chunk>
     */
    public function output(): Sequence
    {
        return $this->output;
    }

    /**
     * @return Either<TimedOut|Failed|Signaled, Success>
     */
    public function wait(): Either
    {
        return Either::right(new Success($this->output));
    }
}
