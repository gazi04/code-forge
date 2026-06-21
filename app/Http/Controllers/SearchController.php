<?php

namespace App\Http\Controllers;

use App\Services\ContentSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(protected ContentSearchService $search) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->search->search($request->user(), (string) $request->string('q')),
        );
    }
}
