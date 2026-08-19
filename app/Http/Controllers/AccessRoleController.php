<?php

namespace App\Http\Controllers;

use App\Models\AccessRole;
use App\Models\Permission;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AccessRoleController extends Controller
{
    public function index()
    {
        return view('roles.index', [
            'roles' => AccessRole::withCount('users')->with('permissions')->orderByDesc('is_system')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('roles.form', $this->formData(new AccessRole));
    }

    public function store(Request $request)
    {
        $role = $this->save($request, new AccessRole);
        AuditService::log('access_role.created', $role, 'Role dan hak akses ditambahkan');

        return redirect()->route('roles.index')->with('success', "Role {$role->name} berhasil ditambahkan.");
    }

    public function edit(AccessRole $role)
    {
        return view('roles.form', $this->formData($role));
    }

    public function update(Request $request, AccessRole $role)
    {
        $this->save($request, $role);
        AuditService::log('access_role.updated', $role, 'Role dan hak akses diperbarui');

        return redirect()->route('roles.index')->with('success', "Role {$role->name} berhasil diperbarui.");
    }

    public function destroy(AccessRole $role)
    {
        abort_if($role->is_system, 422, 'Role bawaan sistem tidak dapat dihapus. Ubah izin atau buat role baru bila diperlukan.');
        abort_if($role->users()->exists(), 422, 'Role masih digunakan oleh akun pengguna dan tidak dapat dihapus.');

        $name = $role->name;
        $role->delete();
        AuditService::log('access_role.deleted', null, 'Role dihapus', ['name' => $name]);

        return back()->with('success', "Role {$name} dihapus.");
    }

    private function formData(AccessRole $role): array
    {
        $role->load('permissions');

        return [
            'role' => $role,
            'permissions' => Permission::orderBy('group_name')->orderBy('name')->get()->groupBy('group_name'),
        ];
    }

    private function save(Request $request, AccessRole $role): AccessRole
    {
        $data = $request->validate([
            'code' => ['required', 'alpha_dash', 'max:40', Rule::unique('access_roles', 'code')->ignore($role->id)],
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
            'location_scope' => ['required', Rule::in(['all', 'assigned'])],
            'location_type' => ['nullable', Rule::in(['store', 'warehouse', 'any'])],
            'permission_codes' => ['nullable', 'array'],
            'permission_codes.*' => ['string', 'exists:permissions,code'],
        ]);

        if ($role->is_system) {
            $data['code'] = $role->code;
        } else {
            $data['code'] = Str::lower($data['code']);
        }
        $data['location_type'] = $data['location_scope'] === 'all' ? null : ($data['location_type'] ?? 'any');
        $permissionIds = Permission::whereIn('code', $data['permission_codes'] ?? [])->pluck('id')->all();
        unset($data['permission_codes']);

        return DB::transaction(function () use ($role, $data, $permissionIds) {
            $role->fill($data)->save();
            $role->permissions()->sync($permissionIds);

            return $role;
        });
    }
}
