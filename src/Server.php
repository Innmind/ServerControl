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
    #[\NoDiscard]
    public function processes(): Processes;

    #[\NoDiscard]
    public function volumes(): Volumes;

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function reboot(): Attempt;

    /**
     * @return Attempt<SideEffect>
     */
    #[\NoDiscard]
    public function shutdown(): Attempt;
}
