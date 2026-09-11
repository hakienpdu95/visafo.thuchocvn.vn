<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Product\Actions\Backend\UpdateAgriSeedAction;
use Modules\Product\Data\Requests\UpdateAgriSeedData;
use Modules\Product\Models\AgriSeed;

class AgriSeedController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', AgriSeed::class);

        $cropTypes = AgriSeed::query()
            ->select('crop_type')
            ->distinct()
            ->orderBy('crop_type')
            ->pluck('crop_type');

        return view('product::agri_seeds.index', compact('cropTypes'));
    }

    public function edit(AgriSeed $agriSeed)
    {
        $this->authorize('update', $agriSeed);

        return view('product::agri_seeds.edit', compact('agriSeed'));
    }

    public function update(Request $request, AgriSeed $agriSeed, UpdateAgriSeedAction $action): RedirectResponse
    {
        $this->authorize('update', $agriSeed);

        $data = UpdateAgriSeedData::validateAndCreate([
            'is_banned' => $request->boolean('is_banned'),
        ]);
        $action->handle($agriSeed, $data);

        return redirect()->route('backend.master-data.seeds.index')
            ->with('success', 'Đã cập nhật "' . $agriSeed->name . '".');
    }
}
