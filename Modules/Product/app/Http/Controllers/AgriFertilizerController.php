<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Product\Models\AgriFertilizer;

class AgriFertilizerController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', AgriFertilizer::class);

        $categories = AgriFertilizer::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('product::agri_fertilizers.index', compact('categories'));
    }
}
