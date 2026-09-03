<?php

namespace App\Http\Controllers;

use App\Models\AuditAssignment;
use App\Models\Engagement;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EngagementController extends Controller
{
    /**
     * List semua engagement lengkap dengan relasi client & tim assignments.
     */
    public function index(): JsonResponse
    {
        $engagements = Engagement::with([
            'client',
            'assignments.user:id,name,email,role_id',
            'assignments.role:id,name',
        ])
            ->latest()
            ->get();

        return response()->json($engagements);
    }

    /**
     * Buat engagement baru + opsional assign tim awal.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'engagement_code' => 'required|string|max:100|unique:engagements,engagement_code',
            'engagement_year' => 'required|integer|min:2000|max:2100',
            'status' => 'nullable|in:draft,active,closed',
            'assignments' => 'nullable|array',
            'assignments.*.user_id' => 'required|exists:users,id',
            'assignments.*.role_id' => 'required|exists:roles,id',
        ]);

        DB::beginTransaction();
        try {
            $engagement = Engagement::create([
                'client_id' => $validated['client_id'],
                'engagement_code' => $validated['engagement_code'],
                'engagement_year' => $validated['engagement_year'],
                'status' => $validated['status'] ?? 'active',
            ]);

            if (! empty($validated['assignments'])) {
                $now = now();
                foreach ($validated['assignments'] as $assign) {
                    AuditAssignment::updateOrCreate(
                        [
                            'engagement_id' => $engagement->id,
                            'role_id' => $assign['role_id'],
                        ],
                        [
                            'user_id' => $assign['user_id'],
                            'assigned_at' => $now,
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json(
                $engagement->load(['client', 'assignments.user:id,name,email,role_id', 'assignments.role:id,name']),
                201
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['message' => 'Gagal membuat engagement: '.$e->getMessage()], 500);
        }
    }

    /**
     * Detail satu engagement.
     */
    public function show(Engagement $engagement): JsonResponse
    {
        return response()->json(
            $engagement->load([
                'client',
                'assignments.user:id,name,email,role_id',
                'assignments.role:id,name',
                'responses.form',
            ])
        );
    }

    /**
     * Update data engagement & assignment tim.
     */
    public function update(Request $request, Engagement $engagement): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'sometimes|required|exists:clients,id',
            'engagement_code' => 'sometimes|required|string|max:100|unique:engagements,engagement_code,'.$engagement->id,
            'engagement_year' => 'sometimes|required|integer|min:2000|max:2100',
            'status' => 'nullable|in:draft,active,closed',
            'assignments' => 'nullable|array',
            'assignments.*.user_id' => 'required|exists:users,id',
            'assignments.*.role_id' => 'required|exists:roles,id',
        ]);

        DB::beginTransaction();
        try {
            $engagement->update(array_filter([
                'client_id' => $validated['client_id'] ?? null,
                'engagement_code' => $validated['engagement_code'] ?? null,
                'engagement_year' => $validated['engagement_year'] ?? null,
                'status' => $validated['status'] ?? null,
            ], fn ($val) => ! is_null($val)));

            if (isset($validated['assignments'])) {
                // Sync assignments
                $now = now();

                // Hapus assignment yang tidak dimasukkan
                $currentRoleIds = collect($validated['assignments'])->pluck('role_id')->toArray();
                AuditAssignment::where('engagement_id', $engagement->id)
                    ->whereNotIn('role_id', $currentRoleIds)
                    ->delete();

                foreach ($validated['assignments'] as $assign) {
                    AuditAssignment::updateOrCreate(
                        [
                            'engagement_id' => $engagement->id,
                            'role_id' => $assign['role_id'],
                        ],
                        [
                            'user_id' => $assign['user_id'],
                            'assigned_at' => $now,
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json(
                $engagement->load(['client', 'assignments.user:id,name,email,role_id', 'assignments.role:id,name'])
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['message' => 'Gagal meng-update engagement: '.$e->getMessage()], 500);
        }
    }

    /**
     * Hapus engagement.
     */
    public function destroy(Engagement $engagement): JsonResponse
    {
        $engagement->delete();

        return response()->json(['message' => 'Engagement berhasil dihapus']);
    }

    /**
     * Helper endpoint: Ambil master roles & users untuk keperluan dropdown assignment di frontend.
     */
    public function metadata(): JsonResponse
    {
        $roles = Role::whereIn('name', ['Partner', 'Manager', 'Senior', 'Junior'])->get(['id', 'name', 'description']);
        $users = User::with('role:id,name')->get(['id', 'name', 'email', 'role_id']);

        return response()->json([
            'roles' => $roles,
            'users' => $users,
        ]);
    }
}
