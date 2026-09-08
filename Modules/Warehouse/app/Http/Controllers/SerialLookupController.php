<?php

namespace Modules\Warehouse\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Warehouse\Actions\Backend\ResolveRetailItemTagByCodeAction;
use Modules\Warehouse\Queries\GetSerialLifecycleHandler;
use Modules\Warehouse\Queries\GetSerialLifecycleQuery;

class SerialLookupController extends Controller
{
    public function index(Request $request, ResolveRetailItemTagByCodeAction $resolver, GetSerialLifecycleHandler $lifecycleHandler)
    {
        abort_unless(auth()->user()->can('warehouse.view'), 403);

        $code   = trim((string) $request->input('code', ''));
        $tag    = null;
        $events = [];
        $searched = $code !== '';

        if ($searched) {
            $tag = $resolver->handle($code);

            if ($tag) {
                $events = $lifecycleHandler->handle(new GetSerialLifecycleQuery($tag));
            }
        }

        return view('warehouse::serial_lookup.index', compact('code', 'tag', 'events', 'searched'));
    }
}
