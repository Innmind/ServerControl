<?php
declare(strict_types = 1);

namespace Innmind\Server\Control\Server\Command;

use Innmind\Server\Control\Exception\EmptyOptionNotAllowed;

final class Option
{
    private bool $long;
    private string $key;
    private ?string $value;

    private function __construct(bool $long, string $key, string $value = null)
    {

        if ($key === '') {
            throw new EmptyOptionNotAllowed;
        }

        $this->long = $long;
        $this->key = $key;
        $this->value = $value;
    }

    public static function long(string $key, string $value = null): self
    {
        return new self(true, $key, $value);
    }

    public static function short(string $key, string $value = null): self
    {
        return new self(false, $key, $value);
    }

    public function __toString(): string
    {
        if ($this->long) {
            return $this->longString();
        }

        return $this->shortString();
    }

    private function longString(): string
    {
        $string = '--'.$this->key;

        if (is_string($this->value)) {
            $string .= '='.$this->value;
        }

        return (string) new Str($string);
    }

    private function shortString(): string
    {
        $string = new Str('-'.$this->key);

        if (is_string($this->value)) {
            $string .= ' '.new Str($this->value);
        }

        return (string) $string;
    }
}
