<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserOptionsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = $request->input('q', '');

        $rows = User::where('is_active', true)
            ->when($q, fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name']);

        return response()->json($rows->map(fn ($u) => ['id' => $u->id, 'text' => $u->name]));
    }
}
