<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process\Input;

use Innmind\Server\Control\Exception\RuntimeException;
use Innmind\Stream\Readable;

final class Bridge implements \Iterator
{
    private const CHUNK = 8192;

    private Readable $stream;

    public function __construct(Readable $stream)
    {
        $this->stream = $stream;
    }

    /**
     * Todo avoid moving the stream pointer
     *
     * @throws RuntimeException If it fails to seek the position before reading the stream
     */
    public function current(): string
    {
        $position = $this->stream->position();
        $text = $this->stream->read(self::CHUNK)->match(
            static fn($text) => $text->toString(),
            static fn() => '',
        );
        /** @var Readable */
        $this->stream = $this->stream->seek($position)->match(
            static fn($stream) => $stream,
            static fn() => throw new RuntimeException('Failed to seek stream'),
        );

        return $text;
    }

    public function key(): int
    {
        return $this->stream->position()->toInt();
    }

    public function next(): void
    {
        $this->stream->read(self::CHUNK);
    }

    /**
     * @throws RuntimeException If it fails to rewind the stream
     */
    public function rewind(): void
    {
        /** @var Readable */
        $this->stream = $this->stream->rewind()->match(
            static fn($stream) => $stream,
            static fn() => throw new RuntimeException('Failed to rewind the stream'),
        );
    }

    public function valid(): bool
    {
        return !$this->stream->end();
    }
}
