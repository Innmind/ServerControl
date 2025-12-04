<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Run;

use Innmind\Server\Control\{
    Server\Command,
    Server\Process,
};
use Innmind\Immutable\Attempt;

/**
 * @internal
 */
interface Implementation
{
    /**
     * @return Attempt<Process>
     */
    public function __invoke(Command|Command\OverSsh $command): Attempt;
}
