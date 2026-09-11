<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Product\Actions\Backend\UpdateAgriPesticideAction;
use Modules\Product\Data\Requests\UpdateAgriPesticideData;
use Modules\Product\Models\AgriPesticide;

class AgriPesticideController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', AgriPesticide::class);

        $categories = AgriPesticide::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('product::agri_pesticides.index', compact('categories'));
    }

    public function edit(AgriPesticide $agriPesticide)
    {
        $this->authorize('update', $agriPesticide);

        return view('product::agri_pesticides.edit', compact('agriPesticide'));
    }

    public function update(Request $request, AgriPesticide $agriPesticide, UpdateAgriPesticideAction $action): RedirectResponse
    {
        $this->authorize('update', $agriPesticide);

        $data = UpdateAgriPesticideData::validateAndCreate([
            'quarantine_days' => $request->input('quarantine_days'),
            'is_banned'       => $request->boolean('is_banned'),
        ]);
        $action->handle($agriPesticide, $data);

        return redirect()->route('backend.master-data.pesticides.index')
            ->with('success', 'Đã cập nhật "' . $agriPesticide->trade_name . '".');
    }
}
