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
            ->when($request->role, fn ($q) => $q->where('role', $request->role))
            ->paginate($request->integer('limit', 15));

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'nomor_induk' => 'STAFF-' . Str::random(8),
            'nama' => $request->nama,
            'email' => $request->email,
            'role' => $request->role,
            'program_studi_id' => $request->program_studi_id,
            'password' => bcrypt(Str::random(16)),
        ]);

        return new UserResource($user);
    }

    public function show(User $user)
    {
        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());

        return new UserResource($user);
    }

    public function destroy(User $user)
    {
        $user->delete();

    return response()->json(null, 204);
    }
}