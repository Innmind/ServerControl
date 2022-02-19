<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Command;

/**
 * @psalm-immutable
 * @internal
 */
final class Option implements Parameter
{
    private bool $long;
    /** @var non-empty-string */
    private string $key;
    private ?string $value;

    /**
     * @param non-empty-string $key
     */
    private function __construct(bool $long, string $key, string $value = null)
    {
        $this->long = $long;
        $this->key = $key;
        $this->value = $value;
    }

    /**
     * @psalm-pure
     * @param non-empty-string $key
     */
    public static function long(string $key, string $value = null): self
    {
        return new self(true, $key, $value);
    }

    /**
     * @psalm-pure
     * @param non-empty-string $key
     */
    public static function short(string $key, string $value = null): self
    {
        return new self(false, $key, $value);
    }

    public function toString(): string
    {
        if ($this->long) {
            return $this->longString();
        }

        return $this->shortString();
    }

    private function longString(): string
    {
        $string = '--'.$this->key;

        if (\is_string($this->value)) {
            $string .= '='.$this->value;
        }

        return (new Str($string))->toString();
    }

    private function shortString(): string
    {
        $string = (new Str('-'.$this->key))->toString();

        if (\is_string($this->value)) {
            $string .= ' '.(new Str($this->value))->toString();
        }

        return $string;
    }
}
