<?php

namespace App\Support;

final readonly class StoreResult
{
    /**
     * @param  'purchased'|'activated'|'error'  $key
     */
    private function __construct(
        public bool $success,
        public string $key,
        public string $value,
    ) {}

    public static function purchased(string $name): self
    {
        return new self(true, 'purchased', $name);
    }

    public static function activated(string $name): self
    {
        return new self(true, 'activated', $name);
    }

    public static function error(string $message): self
    {
        return new self(false, 'error', $message);
    }

    /**
     * Shape flashed to the session under the `store_result` key.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [$this->key => $this->value];
    }
}
