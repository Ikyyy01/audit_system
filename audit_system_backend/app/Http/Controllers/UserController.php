<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * List semua user beserta role-nya.
     */
    public function index(): JsonResponse
    {
        $users = User::with('role:id,name,description')
            ->withCount(['assignments', 'responses'])
            ->orderBy('role_id')
            ->orderBy('name')
            ->get();

        return response()->json($users);
    }

    /**
     * Ambil master roles untuk dropdown form user.
     */
    public function roles(): JsonResponse
    {
        $roles = Role::orderBy('id')->get(['id', 'name', 'description']);

        return response()->json($roles);
    }

    /**
     * Tambah user / pegawai baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'role_id' => $validated['role_id'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json($user->load('role:id,name,description'), 201);
    }

    /**
     * Detail user.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json($user->load([
            'role:id,name,description',
            'assignments.engagement.client',
            'assignments.role',
        ]));
    }

    /**
     * Update data pegawai (nama, email, role, dan opsional password).
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role_id' => 'sometimes|required|exists:roles,id',
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:6',
        ]);

        $updateData = [
            'name' => $validated['name'] ?? $user->name,
            'email' => $validated['email'] ?? $user->email,
            'role_id' => $validated['role_id'] ?? $user->role_id,
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        return response()->json($user->load('role:id,name,description'));
    }

    /**
     * Hapus pegawai.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        // Proteksi: jangan hapus diri sendiri yang sedang login
        if ($request->user() && $request->user()->id === $user->id) {
            return response()->json(['message' => 'Tidak dapat menghapus akun Anda sendiri yang sedang aktif.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Pegawai berhasil dihapus']);
    }
}
