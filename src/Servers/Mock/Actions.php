<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Servers\Mock;

use Innmind\BlackBox\Runner\Assert;

/**
 * @internal
 */
final class Actions implements \Countable
{
    /**
     * @param \SplQueue<object> $actions
     */
    private function __construct(
        private Assert $assert,
        private \SplQueue $actions,
    ) {
    }

    public static function new(Assert $assert): self
    {
        /** @var \SplQueue<object> */
        $queue = new \SplQueue;

        return new self($assert, $queue);
    }

    #[\Override]
    public function count(): int
    {
        return $this->actions->count();
    }

    public function add(object $action): self
    {
        $actions = clone $this->actions;
        $actions->enqueue($action);

        return new self($this->assert, $actions);
    }

    /**
     * @template T
     *
     * @param class-string<T> $class
     * @param non-empty-string $message
     *
     * @return T
     */
    public function pull(string $class, string $message): object
    {
        if ($this->actions->isEmpty()) {
            $this->assert->fail($message);
        }

        $action = $this->actions->dequeue();

        if ($action instanceof $class) {
            return $action;
        }

        $this->assert->fail($message);
    }
}
