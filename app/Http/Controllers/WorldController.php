<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\WorldResource;
use App\Models\UserWorldCompletion;
use App\Models\World;
use App\Services\CertificateService;
use App\Services\WorldQueryService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WorldController extends Controller
{
    public function __construct(
        protected WorldQueryService $worlds,
        protected CertificateService $certificates,
    ) {}

    public function index()
    {
        return Inertia::render('Student/WorldMap', [
            'worlds' => WorldResource::collection($this->worlds->publishedWithCourses()),
        ]);
    }

    public function show(World $world)
    {
        abort_unless($world->is_published, 404);

        return Inertia::render('Student/WorldDetail', [
            'world' => new WorldResource($this->worlds->loadForDetail($world)),
        ]);
    }

    public function certificate(World $world): Response
    {
        $user = Auth::user();

        $completion = $this->certificates->completionFor($user, $world);
        abort_unless($completion instanceof UserWorldCompletion, 403, 'Certificate not yet earned for this world.');

        $pdf = $this->certificates->renderFor($user, $world, $completion);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="codeforge-'.$world->slug.'-certificate.pdf"',
        ]);
    }
}
