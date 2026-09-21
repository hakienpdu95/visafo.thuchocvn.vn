<?php

namespace Modules\Menu\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Menu\Actions\Backend\DestroyMenuAction;
use Modules\Menu\Actions\Backend\StoreMenuAction;
use Modules\Menu\Actions\Backend\UpdateMenuAction;
use Modules\Menu\Data\Requests\StoreMenuData;
use Modules\Menu\Models\Menu;
use Modules\Menu\Queries\GetMenuFormOptionsHandler;
use Modules\Menu\Queries\GetMenuFormOptionsQuery;

class MenuController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Menu::class, 'menu');
    }

    public function index(GetMenuFormOptionsHandler $options)
    {
        return view('menu::menus.index', $options->handle(new GetMenuFormOptionsQuery()));
    }

    public function create(GetMenuFormOptionsHandler $options)
    {
        return view('menu::menus.create', $options->handle(new GetMenuFormOptionsQuery()));
    }

    public function store(Request $request, StoreMenuAction $action): RedirectResponse
    {
        $action->handle(StoreMenuData::validateAndCreate($request->all()));

        return redirect()->route('backend.menus.index')->with('success', 'Đã tạo thực đơn.');
    }

    public function edit(Menu $menu, GetMenuFormOptionsHandler $options)
    {
        return view('menu::menus.edit', ['menu' => $menu->load('dishes', 'customer:id,name')] + $options->handle(new GetMenuFormOptionsQuery()));
    }

    public function update(Request $request, Menu $menu, UpdateMenuAction $action): RedirectResponse
    {
        $action->handle($menu, StoreMenuData::validateAndCreate($request->all()));

        return redirect()->route('backend.menus.index')->with('success', 'Đã cập nhật thực đơn.');
    }

    public function destroy(Request $request, Menu $menu, DestroyMenuAction $action): RedirectResponse|JsonResponse
    {
        $label = $action->handle($menu);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã xóa thực đơn ' . $label . '.']);
        }

        return redirect()->route('backend.menus.index')->with('success', 'Đã xóa thực đơn ' . $label . '.');
    }
}
