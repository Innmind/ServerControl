<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers;

use Innmind\Server\Control\Server\Processes;

/**
 * @internal
 */
interface Implementation
{
    #[\NoDiscard]
    public function processes(): Processes;
}
