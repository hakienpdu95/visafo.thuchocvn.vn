<?php

namespace Modules\Product\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Product\Http\Resources\AgriFertilizerMobileResource;
use Modules\Product\Models\AgriFertilizer;

class FertilizerMobileApiController extends Controller
{
    public function index(): JsonResponse
    {
        $fertilizers = AgriFertilizer::query()
            ->where('status', 'active')
            ->where('is_banned', false)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => AgriFertilizerMobileResource::collection($fertilizers),
        ]);
    }
}
