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
use Symfony\Component\Process\Process;

final class BackgroundProcess implements ProcessInterface
{
    private Output $output;

    public function __construct(Process $process)
    {
        //read process pipes once otherwise the process will be killed
        $process->getIterator()->next();
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
