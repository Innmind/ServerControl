<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

final class Failed
{
    private ExitCode $exitCode;
    private Output $output;

    /**
     * @internal
     */
    public function __construct(ExitCode $exitCode, Output $output)
    {
        $this->exitCode = $exitCode;
        $this->output = $output;
    }

    public function exitCode(): ExitCode
    {
        return $this->exitCode;
    }

    public function output(): Output
    {
        return $this->output;
    }
}
