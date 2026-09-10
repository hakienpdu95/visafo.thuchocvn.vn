<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ward;
use Illuminate\Http\JsonResponse;

class WardController extends Controller
{
    public function forProvince(string $provinceCode): JsonResponse
    {
        $wards = Ward::where('province_code', $provinceCode)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['ward_code', 'name']);

        return response()->json($wards);
    }
}
