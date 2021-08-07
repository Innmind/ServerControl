<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Exception;

use Innmind\Server\Control\Server\Process\ExitCode;

final class ProcessFailed extends RuntimeException
{
    private ExitCode $exitCode;

    public function __construct(ExitCode $exitCode)
    {
        $this->exitCode = $exitCode;
    }

    public function exitCode(): ExitCode
    {
        return $this->exitCode;
    }
}
