<?php

namespace Modules\Product\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Product\Http\Resources\AgriSeedMobileResource;
use Modules\Product\Models\AgriSeed;

class SeedMobileApiController extends Controller
{
    public function index(): JsonResponse
    {
        $seeds = AgriSeed::query()
            ->where('status', 'active')
            ->where('is_banned', false)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => AgriSeedMobileResource::collection($seeds),
        ]);
    }
}
