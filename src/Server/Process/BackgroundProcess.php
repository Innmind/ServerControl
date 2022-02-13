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
