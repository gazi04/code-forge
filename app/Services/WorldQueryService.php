<?php

namespace App\Services;

use App\Models\World;
use Illuminate\Database\Eloquent\Collection;

class WorldQueryService
{
    /**
     * Published worlds for the world map, ordered, each with its theme and
     * published course metadata (no heavy fields).
     *
     * @return Collection<int, World>
     */
    public function publishedWithCourses(): Collection
    {
        return World::published()
            ->ordered()
            ->with([
                'themePack',
                'courses' => fn ($query) => $query
                    ->published()
                    ->select(['id', 'world_id', 'name', 'slug', 'description', 'min_level_requirement']),
            ])
            ->get();
    }

    /**
     * Eager-load the theme and published courses for a single world's detail view.
     */
    public function loadForDetail(World $world): World
    {
        return $world->load(['themePack', 'courses' => fn ($query) => $query->published()]);
    }
}
