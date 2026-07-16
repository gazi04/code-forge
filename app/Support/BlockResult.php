<?php

declare(strict_types=1);

namespace App\Support;

final readonly class BlockResult
{
    /**
     * @param  'success'|'already_completed'|'incorrect'|'out_of_bounds'  $status
     * @param  array<string, mixed>  $gameResult
     */
    public function __construct(
        public string $status,
        public array $gameResult = [],
    ) {}
}
