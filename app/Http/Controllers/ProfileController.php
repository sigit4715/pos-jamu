<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();
        $user->update(['password' => Hash::make($data['password'])]);
        AuditService::log('user.password_updated', $user, 'Pengguna mengganti password sendiri');

        return redirect()->route('profile.edit')->with('success', 'Password berhasil diperbarui.');
    }
}
