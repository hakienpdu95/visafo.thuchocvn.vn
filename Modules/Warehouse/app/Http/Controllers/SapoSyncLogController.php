<?php

namespace Modules\Warehouse\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SapoSyncLogController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->can('warehouse.view'), 403);

        return view('warehouse::sapo_sync_log.index');
    }
}
