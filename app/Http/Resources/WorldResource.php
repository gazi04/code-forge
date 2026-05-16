<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorldResource extends JsonResource
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
            'description' => $this->description,
            // Pass the ThemePack as a clean object for CSS variables
            'theme' => [
                'config' => $this->themePack->config,
            ],
            // Only include courses if they are loaded
            'courses' => CourseResource::collection($this->whenLoaded('courses')),
        ];
    }
}
