<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

use Innmind\Server\Control\Server\Process as ProcessInterface;
use Innmind\Immutable\{
    Sequence,
    Str,
    Maybe,
    Either,
};

final class ForegroundProcess implements ProcessInterface
{
    private StartedProcess $process;
    private Output $output;

    public function __construct(StartedProcess $process, bool $streamOutput = false)
    {
        $this->process = $process;

        if ($streamOutput) {
            $output = Sequence::lazy(static function() use ($process) {
                yield from $process->output();
            });
        } else {
            $output = Sequence::defer($process->output());
        }

        $this->output = new Output\Output($output);
    }

    public function pid(): Maybe
    {
        return Maybe::of($this->process->pid());
    }

    public function output(): Output
    {
        return $this->output;
    }

    public function wait(): Either
    {
        return $this->process->wait();
    }
}
