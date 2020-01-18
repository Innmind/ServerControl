<?php
declare(strict_types = 1);

namespace Innmind\Server\Control;

use Innmind\Server\Control\Server\{
    Processes,
    Volumes,
};

interface Server
{
    public function processes(): Processes;
    public function volumes(): Volumes;
}
