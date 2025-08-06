<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers\Mock;

use Innmind\Server\Control\Server\{
    Command,
    Process,
};

/**
 * @internal
 */
final class Execute
{
    /**
     * @param \Closure(Command): void $assert
     * @param \Closure(Command, ProcessBuilder): ProcessBuilder $build
     */
    private function __construct(
        private \Closure $assert,
        private \Closure $build,
    ) {
    }

    public static function of(
        callable $assert,
        callable $build,
    ): self {
        /** @psalm-suppress MixedArgumentTypeCoercion */
        return new self(
            \Closure::fromCallable($assert),
            \Closure::fromCallable($build),
        );
    }

    public function run(Command $command): Process
    {
        ($this->assert)($command);

        return ($this->build)($command, ProcessBuilder::new())->build();
    }
}
