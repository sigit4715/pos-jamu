<?php

namespace App\Http\Controllers;

use App\Models\AccessRole;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminMenuPreviewController extends Controller
{
    public function update(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'role' => ['required', Rule::in(['admin', 'kasir', 'gudang'])],
        ]);

        abort_unless(AccessRole::where('code', $data['role'])->exists(), 422);
        session(['menu_preview_role' => $data['role']]);

        return back()->with('success', 'Tampilan menu berhasil diganti. Hak akses administrator tetap sama.');
    }
}
