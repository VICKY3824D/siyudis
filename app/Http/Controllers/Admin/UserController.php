<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->with('role')
            ->when($request->role_id, fn ($q) => $q->where('role_id', $request->role_id))
            ->paginate($request->integer('limit', 15));

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'nomor_induk' => 'STAFF-' . Str::random(8),
            'nama' => $request->nama,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'program_studi_id' => $request->program_studi_id,
            'password' => bcrypt(Str::random(16)),
        ]);

        return new UserResource($user->load('role'));
    }

    public function show(User $user)
    {
        return new UserResource($user->load('role'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());

        return new UserResource($user->load('role'));
    }

    public function destroy(User $user)
    {
        // $isReferenced = DB::table('pengajuan_yudisium')
        //     ->where('checked_akademik_by', $user->id)
        //     ->orWhere('acc_kaprodi_by', $user->id)
        //     ->orWhere('acc_manit_by', $user->id)
        //     ->orWhere('acc_kadep_by', $user->id)
        //     ->exists();

        // if ($isReferenced) {
        //     return response()->json(['message' => 'User masih terkait proses pengajuan yang berjalan'], 409);
        // }

        $user->delete();

        return response()->json(null, 204);
    }
}