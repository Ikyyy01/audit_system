<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(): JsonResponse
    {
        $clients = Client::with('engagements')->latest()->get();

        return response()->json($clients);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'client_type' => 'required|in:TBK,Non-TBK',
            'address' => 'nullable|string',
        ]);

        $client = Client::create($validated);

        return response()->json($client, 201);
    }

    public function show(Client $client): JsonResponse
    {
        return response()->json($client->load('engagements'));
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'client_type' => 'sometimes|required|in:TBK,Non-TBK',
            'address' => 'nullable|string',
        ]);

        $client->update($validated);

        return response()->json($client);
    }

    public function destroy(Client $client): JsonResponse
    {
        $client->delete();

        return response()->json(['message' => 'Client deleted successfully']);
    }
}
