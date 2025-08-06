<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers\Mock;

use Innmind\Server\Control\Server\{
    Process,
    Process\Success,
    Process\Signaled,
    Process\TimedOut,
    Process\Failed,
    Process\ExitCode,
    Process\Output\Chunk,
    Process\Mock,
};
use Innmind\Immutable\Sequence;

final class ProcessBuilder
{
    private function __construct(
        private Success|Signaled|TimedOut|Failed $result,
    ) {
    }

    /**
     * @internal
     */
    public static function new(): self
    {
        return new self(new Success(Sequence::of()));
    }

    /**
     * @param Sequence<Chunk> $output
     */
    #[\NoDiscard]
    public function success(?Sequence $output = null): self
    {
        return new self(new Success($output ?? Sequence::of()));
    }

    /**
     * @param Sequence<Chunk> $output
     */
    #[\NoDiscard]
    public function signaled(?Sequence $output = null): self
    {
        return new self(new Signaled($output ?? Sequence::of()));
    }

    /**
     * @param Sequence<Chunk> $output
     */
    #[\NoDiscard]
    public function timedOut(?Sequence $output = null): self
    {
        return new self(new TimedOut($output ?? Sequence::of()));
    }

    /**
     * @param int<1, 255> $exitCode
     * @param Sequence<Chunk> $output
     */
    #[\NoDiscard]
    public function failed(int $exitCode = 1, ?Sequence $output = null): self
    {
        return new self(new Failed(
            new ExitCode($exitCode),
            $output ?? Sequence::of(),
        ));
    }

    /**
     * @internal
     */
    #[\NoDiscard]
    public function build(): Process
    {
        $result = $this->result;

        /**
         * This a trick to not expose any mock contructor on the Process class.
         *
         * @psalm-suppress PossiblyNullFunctionCall
         * @psalm-suppress MixedReturnStatement
         * @psalm-suppress InaccessibleMethod
         */
        return (\Closure::bind(
            static fn() => new Process(new Mock($result)),
            null,
            Process::class,
        ))();
    }
}
