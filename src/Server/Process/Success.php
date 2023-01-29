<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process;

final class Success
{
    private Output $output;

    /**
     * @internal
     */
    public function __construct(Output $output)
    {
        $this->output = $output;
    }

    public function output(): Output
    {
        return $this->output;
    }
}
