<?php

namespace Modules\Product\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Product\Http\Resources\AgriPesticideMobileResource;
use Modules\Product\Models\AgriPesticide;

class PesticideMobileApiController extends Controller
{
    public function index(): JsonResponse
    {
        $pesticides = AgriPesticide::query()
            ->where('status', 'active')
            ->where('is_banned', false)
            ->orderBy('trade_name')
            ->get();

        return response()->json([
            'data' => AgriPesticideMobileResource::collection($pesticides),
        ]);
    }
}
