<?php
declare(strict_types = 1);

namespace Innmind\Server\Control;

use Innmind\Server\Control\{
    Server\Processes,
    Server\Volumes,
};
use Innmind\Immutable\{
    Attempt,
    SideEffect,
};

interface Server
{
    public function processes(): Processes;
    public function volumes(): Volumes;

    /**
     * @return Attempt<SideEffect>
     */
    public function reboot(): Attempt;

    /**
     * @return Attempt<SideEffect>
     */
    public function shutdown(): Attempt;
}
