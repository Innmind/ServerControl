<?php
declare(strict_types = 1);

namespace Innmind\Server\Control;

use Innmind\Server\Control\Server\Process\ExitCode;

final class ProcessFailed
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
