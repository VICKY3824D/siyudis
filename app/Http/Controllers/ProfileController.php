<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Tampilkan profil user yang sedang login.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load(['programStudi', 'role']);

        return response()->json([
            'status' => 'success',
            'data' => $user,
        ]);
    }

    /**
     * Update profil user yang sedang login.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($request->hasFile('avatar')) {
            if ($user->avatar && str_contains($user->avatar, '/storage/avatars/')) {
                $oldPath = str_replace(asset('storage/'), '', $user->avatar);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = asset('storage/'.$path);
        } elseif (array_key_exists('avatar', $validated) && $validated['avatar'] === null && $user->avatar && str_contains($user->avatar, '/storage/avatars/')) {
            $oldPath = str_replace(asset('storage/'), '', $user->avatar);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        if (array_key_exists('password', $validated) && empty($validated['password'])) {
            unset($validated['password']);
        }

        // Pastikan role_id dan google_id tidak dapat diubah via profile update
        unset($validated['role_id'], $validated['google_id']);

        $user->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui',
            'data' => $user->fresh()->load(['programStudi', 'role']),
        ]);
    }
}
