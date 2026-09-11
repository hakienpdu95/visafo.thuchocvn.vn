<?php

namespace Modules\Product\Http\Controllers\Farmer;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use Modules\Product\Models\FarmingBatch;
use Modules\Product\Models\FarmingLog;

class FarmerDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $query = FarmingBatch::query()
            ->where('status', 'active')
            ->with(['agriSeed', 'farmingSource', 'vendor'])
            ->orderByDesc('created_at');

        if ($user->hasRole(RoleEnum::FARMER->value)) {
            $query->where('vendor_id', $user->vendor_id);
        }

        $batches = $query->get();

        $batches->each(function (FarmingBatch $batch) {
            $batch->setRelation('recentLogs', FarmingLog::query()
                ->where('farming_batch_id', $batch->id)
                ->with('vendorFarmingStep')
                ->latest('created_at')
                ->limit(5)
                ->get());
        });

        return view('product::farmer.dashboard', compact('batches'));
    }
}
