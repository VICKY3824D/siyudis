<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Mengembalikan Auth URL Google ke Frontend.
     */
    public function redirectToGoogle(): JsonResponse
    {
        $url = Socialite::driver('google')
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json([
            'status' => 'success',
            'url' => $url,
        ]);
    }

    /**
     * Memproses callback OAuth dari Google & menerbitkan token Sanctum.
     */
    public function handleGoogleCallback(Request $request): JsonResponse
    {
        try {
            /** @var \Laravel\Socialite\Two\User $googleUser */
            $googleUser = Socialite::driver('google')->stateless()->user();
            $email = $googleUser->getEmail();

            // Validasi Domain Email UGM
            if (! str_ends_with($email, '@ugm.ac.id') && ! str_ends_with($email, '@mail.ugm.ac.id')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Login gagal. Wajib menggunakan akun email resmi UGM (@ugm.ac.id / @mail.ugm.ac.id).',
                ], 403);
            }

            // Cari atau buat User baru
            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $email)
                ->first();

            if (! $user) {
                // Ambil NIM/NIP sementara dari prefix email jika user baru
                $nomorInduk = explode('@', $email)[0];

                $user = User::create([
                    'nomor_induk' => $nomorInduk,
                    'nama' => $googleUser->getName(),
                    'email' => $email,
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'role' => 'mahasiswa', // Default role
                ]);
            } else {
                // Update google_id & avatar jika user sudah ada
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                ]);
            }

            // Terbitkan Sanctum Bearer Token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Login berhasil',
                'data' => [
                    'user' => $user->load('programStudi'),
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Autentikasi Google gagal: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout & hapus token aktif.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil logout',
        ]);
    }
}
