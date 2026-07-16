<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Support\BlockSanitizer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'xp_reward' => $this->xp_reward,
            'coin_reward' => $this->coin_reward,
            'is_boss' => $this->is_boss,
            'estimated_duration' => $this->estimated_duration,
            // Filament's Builder data passed to Svelte, with answer keys stripped
            'blocks' => resolve(BlockSanitizer::class)->sanitize($this->blocks ?? []),
            'sort_order' => $this->sort_order,
        ];
    }
}
