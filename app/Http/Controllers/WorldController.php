<?php

namespace App\Http\Controllers;

use App\Http\Resources\WorldResource;
use App\Models\World;
use Inertia\Inertia;

class WorldController extends Controller
{
    public function index()
    {
        $worlds = World::with('themePack', 'courses')->get();

        return Inertia::render('Student/WorldMap', [
            'worlds' => WorldResource::collection($worlds)
        ]);
    }

    public function show(World $world)
    {
        // Load courses and their themes for the specific world view
        $world->load(['themePack', 'courses']);

        return Inertia::render('Student/WorldDetail', [
            'world' => new WorldResource($world)
        ]);
    }
}
