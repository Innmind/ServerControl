<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

final class Failed
{
    private ExitCode $exitCode;

    /**
     * @internal
     */
    public function __construct(ExitCode $exitCode)
    {
        $this->exitCode = $exitCode;
    }

    public function exitCode(): ExitCode
    {
        return $this->exitCode;
    }
}
