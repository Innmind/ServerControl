<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process\Output;

use Innmind\Server\Control\Server\{
    Process\Output,
    Command,
};
use Innmind\Immutable\{
    Map,
    Str,
    SideEffect,
};
use Psr\Log\LoggerInterface;

final class Logger implements Output
{
    private Output $output;
    private Command $command;
    private LoggerInterface $logger;

    public function __construct(
        Output $output,
        Command $command,
        LoggerInterface $logger
    ) {
        $this->output = $output;
        $this->command = $command;
        $this->logger = $logger;
    }

    public function foreach(callable $function): SideEffect
    {
        return $this->output->foreach(function(Str $output, Type $type) use ($function): void {
            $method = ($type === Type::output()) ? 'debug' : 'warning';
            $this->logger->$method('Command {command} output', [
                'command' => $this->command->toString(),
                'output' => $output->toString(),
                'type' => $type->toString(),
            ]);

            $function($output, $type);
        });
    }

    public function reduce($carry, callable $reducer)
    {
        return $this->output->reduce($carry, $reducer);
    }

    public function filter(callable $predicate): Output
    {
        return new self(
            $this->output->filter($predicate),
            $this->command,
            $this->logger,
        );
    }

    public function groupBy(callable $discriminator): Map
    {
        return $this->output->groupBy($discriminator);
    }

    public function partition(callable $predicate): Map
    {
        return $this->output->partition($predicate);
    }

    public function toString(): string
    {
        return $this->output->toString();
    }
}
