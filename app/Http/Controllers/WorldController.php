<?php

namespace App\Http\Controllers;

use App\Http\Resources\WorldResource;
use App\Models\World;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WorldController extends Controller
{
    public function index()
    {
        $worlds = World::published()
            ->with(['themePack', 'courses' => fn ($query) => $query->published()])
            ->get();

        return Inertia::render('Student/WorldMap', [
            'worlds' => WorldResource::collection($worlds),
        ]);
    }

    public function show(World $world)
    {
        abort_unless($world->is_published, 404);

        // Load courses and their themes for the specific world view
        $world->load(['themePack', 'courses' => fn ($query) => $query->published()]);

        return Inertia::render('Student/WorldDetail', [
            'world' => new WorldResource($world),
        ]);
    }

    public function certificate(World $world): Response
    {
        $user = Auth::user();

        $completion = $user->worldCompletions()->where('world_id', $world->id)->first();

        abort_unless($completion !== null, 403, 'Certificate not yet earned for this world.');

        $world->load('themePack', 'courses');
        $primaryColor = $world->themePack?->config['palette']['primary'] ?? '#8b5cf6';

        return Pdf::loadView('certificates.world', [
            'user' => $user,
            'world' => $world,
            'courses' => $world->courses,
            'completedAt' => $completion->completed_at,
            'primaryColor' => $primaryColor,
        ])->setPaper('a4', 'landscape')->download("codeforge-{$world->slug}-certificate.pdf");
    }
}
