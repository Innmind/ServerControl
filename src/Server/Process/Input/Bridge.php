<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Process\Input;

use Innmind\Filesystem\StreamInterface;

final class Bridge implements \Iterator
{
    private const CHUNK = 8192;

    private $stream;

    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;
    }

    public function current(): string
    {
        $position = $this->stream->position();
        $text = $this->stream->read(self::CHUNK);
        $this->stream->seek($position);

        return $text;
    }

    public function key(): int
    {
        return $this->stream->position();
    }

    public function next(): void
    {
        $this->stream->read(self::CHUNK);
    }

    public function rewind(): void
    {
        $this->stream->rewind();
    }

    public function valid(): bool
    {
        return !$this->stream->isEof();
    }
}
