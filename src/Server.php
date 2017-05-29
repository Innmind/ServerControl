<?php
declare(strict_types = 1);

namespace Innmind\Server\Control;

use Innmind\Server\Control\Server\Processes;

interface Server
{
    public function processes(): Processes;
}
