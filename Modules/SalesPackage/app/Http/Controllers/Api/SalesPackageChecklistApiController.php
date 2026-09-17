<?php

namespace Modules\SalesPackage\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Customer\Models\Customer;
use Modules\SalesPackage\Models\SalesPackage;
use Modules\SalesPackage\Queries\BuildPackageChecklistHandler;
use Modules\SalesPackage\Queries\BuildPackageChecklistQuery;

class SalesPackageChecklistApiController extends Controller
{
    public function show(Customer $customer, BuildPackageChecklistHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', SalesPackage::class);

        $result = $handler->handle(new BuildPackageChecklistQuery($customer));

        return response()->json([
            'customer' => [
                'id'   => $result['customer']->id,
                'name' => $result['customer']->name,
            ],
            'expected_deadline' => $result['expected_deadline'],
            'items'             => $result['items'],
            'total'             => $result['total'],
            'satisfied_count'   => $result['satisfied_count'],
            'score'             => $result['score'],
        ]);
    }
}
