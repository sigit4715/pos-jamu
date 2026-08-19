<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\Permission;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MenuItemController extends Controller
{
    private const ICONS = ['dashboard', 'cart', 'receipt', 'clock', 'users', 'package-plus', 'package-minus', 'boxes', 'calendar', 'wallet', 'clipboard', 'undo', 'arrows', 'cube', 'database', 'barcode', 'tag', 'coins', 'landmark', 'search', 'user', 'settings', 'menu', 'chart', 'trending', 'download'];

    public function index()
    {
        return view('menus.index', [
            'menus' => MenuItem::with('permission')->orderBy('section')->orderBy('sort_order')->get()->groupBy('section'),
            'permissions' => Permission::orderBy('group_name')->orderBy('name')->get(),
            'icons' => self::ICONS,
        ]);
    }

    public function update(Request $request, MenuItem $menuItem)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'section' => ['required', 'string', 'max:50'],
            'icon' => ['required', Rule::in(self::ICONS)],
            'permission_id' => ['required', 'integer', 'exists:permissions,id'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $menuItem->update($data);
        AuditService::log('menu_item.updated', $menuItem, 'Pengaturan menu sidebar diperbarui');

        return back()->with('success', "Menu {$menuItem->name} diperbarui.");
    }
}
