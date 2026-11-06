<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $materials = Material::orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $materials,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'unit' => ['sometimes', 'required', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        if (! isset($validated['unit'])) {
            $validated['unit'] = 'Pcs';
        }

        $material = Material::create($validated);

        // Log terstruktur: Menyimpan data (Level: info)
        Log::info('Material data successfully created', [
            'who' => [
                'user_id' => $request->user()?->id,
                'email'   => $request->user()?->email,
                'ip'      => $request->ip(),
            ],
            'what' => [
                'action'        => 'create_material',
                'material_id'   => $material->id,
                'material_name' => $material->name,
                'category'      => $material->category,
                'unit'          => $material->unit,
                'price'         => $material->price,
                'stock'         => $material->stock,
            ],
            'reason' => 'Material successfully validated and persisted to database',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Material created successfully',
            'data' => $material,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $material = Material::find($id);

        if (! $material) {
            return response()->json([
                'status' => 'error',
                'message' => 'Material not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $material,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $material = Material::find($id);

        if (! $material) {
            return response()->json([
                'status' => 'error',
                'message' => 'Material not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'category' => ['sometimes', 'required', 'string', 'max:100'],
            'unit' => ['sometimes', 'required', 'string', 'max:50'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'stock' => ['sometimes', 'required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $material->update($validated);

        // Log terstruktur: Memperbarui data (Level: info)
        Log::info('Material data successfully updated', [
            'who' => [
                'user_id' => $request->user()?->id,
                'email'   => $request->user()?->email,
                'ip'      => $request->ip(),
            ],
            'what' => [
                'action'        => 'update_material',
                'material_id'   => $material->id,
                'material_name' => $material->name,
                'updated_fields'=> array_keys($validated),
            ],
            'reason' => 'Material successfully updated and persisted to database',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Material updated successfully',
            'data' => $material,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $material = Material::find($id);

        if (! $material) {
            return response()->json([
                'status' => 'error',
                'message' => 'Material not found',
            ], 404);
        }

        $material->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Material deleted successfully',
        ], 200);
    }
}

