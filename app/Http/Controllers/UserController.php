<?php

namespace App\Http\Controllers;

use App\Models\AccessRole;
use App\Models\Permission;
use App\Models\Store;
use App\Models\User;
use App\Models\UserPermissionOverride;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index', ['users' => User::with(['store', 'accessRole'])->latest()->get()]);
    }

    public function create()
    {
        return view('users.form', $this->formData(new User));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $user = DB::transaction(function () use ($data) {
            $user = User::create($data['attributes'] + ['password' => Hash::make($data['password'])]);
            $this->syncOverrides($user, $data['overrides']);

            return $user;
        });
        AuditService::log('user.created', $user, 'Akun pengguna ditambahkan');

        return redirect()->route('users.index')->with('success', 'Akun berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('users.form', $this->formData($user));
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);
        DB::transaction(function () use ($data, $user) {
            $attributes = $data['attributes'];
            if (filled($data['password'] ?? null)) {
                $attributes['password'] = Hash::make($data['password']);
            }
            $user->update($attributes);
            $this->syncOverrides($user, $data['overrides']);
        });
        AuditService::log('user.updated', $user, 'Akun pengguna diperbarui');

        return redirect()->route('users.index')->with('success', 'Akun pengguna diperbarui.');
    }

    private function formData(User $user): array
    {
        return [
            'user' => $user->load(['accessRole', 'permissionOverrides.permission']),
            'stores' => Store::where('is_active', true)->orderBy('name')->get(),
            'roles' => AccessRole::orderByDesc('is_system')->orderBy('name')->get(),
            'permissions' => Permission::orderBy('group_name')->orderBy('name')->get()->groupBy('group_name'),
        ];
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user?->id)],
            'access_role_id' => ['required', 'integer', 'exists:access_roles,id'],
            'store_id' => ['nullable', 'integer', Rule::exists('stores', 'id')->where('is_active', true)],
            'password' => [$user ? 'nullable' : 'required', 'min:6', 'confirmed'],
            'permission_overrides' => ['nullable', 'array'],
            'permission_overrides.*' => [Rule::in(['inherit', 'allow', 'deny'])],
        ]);
        $role = AccessRole::findOrFail($data['access_role_id']);
        $storeId = $data['store_id'] ?? null;
        if ($role->location_scope === 'assigned' && ! $storeId) {
            throw ValidationException::withMessages(['store_id' => 'Role ini harus ditugaskan ke toko atau gudang.']);
        }
        if ($storeId && $role->location_scope === 'assigned' && $role->location_type && $role->location_type !== 'any') {
            $store = Store::findOrFail($storeId);
            if ($store->type !== $role->location_type) {
                throw ValidationException::withMessages(['store_id' => 'Role ini hanya dapat ditugaskan ke lokasi yang sesuai.']);
            }
        }

        $validPermissionIds = Permission::whereIn('id', array_keys($data['permission_overrides'] ?? []))->pluck('id')->flip();
        $overrides = collect($data['permission_overrides'] ?? [])
            ->filter(fn (string $state) => $state !== 'inherit')
            ->map(fn (string $state, string $permissionId) => ['permission_id' => (int) $permissionId, 'is_allowed' => $state === 'allow'])
            ->filter(fn (array $row) => $validPermissionIds->has($row['permission_id']))
            ->values()
            ->all();

        return [
            'attributes' => [
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => $role->code,
                'access_role_id' => $role->id,
                'store_id' => $role->location_scope === 'all' ? null : $storeId,
            ],
            'password' => $data['password'] ?? null,
            'overrides' => $overrides,
        ];
    }

    private function syncOverrides(User $user, array $rows): void
    {
        UserPermissionOverride::where('user_id', $user->id)->delete();
        foreach ($rows as $row) {
            UserPermissionOverride::create(['user_id' => $user->id] + $row);
        }
    }
}
