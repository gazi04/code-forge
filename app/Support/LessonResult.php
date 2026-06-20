<?php

namespace App\Support;

final readonly class LessonResult
{
    /**
     * @param  'success'|'already_completed'|'requirements_not_met'  $status
     * @param  array<string, mixed>  $gameResult
     */
    public function __construct(
        public string $status,
        public array $gameResult = [],
        public int $levelBefore = 0,
    ) {}
}
