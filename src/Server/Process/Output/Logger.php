<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process\Output;

use Innmind\Server\Control\Server\{
    Process\Output,
    Command,
};
use Innmind\Immutable\{
    Map,
    SideEffect,
    Sequence,
};
use Psr\Log\LoggerInterface;

/**
 * @psalm-immutable
 */
final class Logger implements Output
{
    private Output $output;
    private Command $command;
    private LoggerInterface $logger;

    private function __construct(
        Output $output,
        Command $command,
        LoggerInterface $logger,
    ) {
        $this->output = $output;
        $this->command = $command;
        $this->logger = $logger;
    }

    public static function psr(
        Output $output,
        Command $command,
        LoggerInterface $logger,
    ): self {
        return new self($output, $command, $logger);
    }

    #[\Override]
    public function foreach(callable $function): SideEffect
    {
        return $this->output->foreach(function($chunk) use ($function): void {
            $method = match ($chunk->type()) {
                Type::output => 'debug',
                Type::error => 'warning',
            };
            $this->logger->$method('Command {command} output', [
                'command' => $this->command->toString(),
                'output' => $chunk->data()->toString(),
                'type' => $chunk->type()->toString(),
            ]);

            $function($chunk);
        });
    }

    #[\Override]
    public function reduce($carry, callable $reducer)
    {
        return $this->output->reduce($carry, $reducer);
    }

    #[\Override]
    public function filter(callable $predicate): Output
    {
        return new self(
            $this->output->filter($predicate),
            $this->command,
            $this->logger,
        );
    }

    #[\Override]
    public function groupBy(callable $discriminator): Map
    {
        return $this->output->groupBy($discriminator);
    }

    #[\Override]
    public function partition(callable $predicate): Map
    {
        return $this->output->partition($predicate);
    }

    #[\Override]
    public function toString(): string
    {
        return $this->output->toString();
    }

    #[\Override]
    public function chunks(): Sequence
    {
        return $this->output->chunks();
    }
}
