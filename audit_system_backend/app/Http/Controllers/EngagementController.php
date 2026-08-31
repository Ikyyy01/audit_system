<?php

namespace App\Http\Controllers;

use App\Models\Engagement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EngagementController extends Controller
{
    public function index(): JsonResponse
    {
        $engagements = Engagement::with(['client', 'assignments.user', 'assignments.role'])->latest()->get();

        return response()->json($engagements);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'engagement_code' => 'required|string|max:100|unique:engagements,engagement_code',
            'engagement_year' => 'required|integer|min:2000|max:2100',
            'status' => 'nullable|string',
        ]);

        $engagement = Engagement::create($validated);

        return response()->json($engagement, 201);
    }

    public function show(Engagement $engagement): JsonResponse
    {
        return response()->json($engagement->load(['client', 'assignments.user', 'assignments.role']));
    }

    public function update(Request $request, Engagement $engagement): JsonResponse
    {
        $validated = $request->validate([
            'engagement_code' => 'sometimes|required|string|max:100|unique:engagements,engagement_code,'.$engagement->id,
            'engagement_year' => 'sometimes|required|integer|min:2000|max:2100',
            'status' => 'nullable|string',
        ]);

        $engagement->update($validated);

        return response()->json($engagement);
    }

    public function destroy(Engagement $engagement): JsonResponse
    {
        $engagement->delete();

        return response()->json(['message' => 'Engagement deleted successfully']);
    }
}
