<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Unit::with(['instansi:id,nama,singkatan', 'parent:id,nama']);

        if ($request->filled('instansi_id')) {
            $query->where('instansi_id', $request->integer('instansi_id'));
        }

        if ($request->filled('aktif')) {
            $query->where('aktif', $request->boolean('aktif'));
        }

        if ($request->filled('q')) {
            $keyword = $request->input('q');

            $query->where(function ($q) use ($keyword) {
                $q->where('nama', 'ilike', "%{$keyword}%")
                    ->orWhere('kode', 'ilike', "%{$keyword}%");
            });
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar unit berhasil diambil.',
            'data' => $query->orderBy('nama')
                ->paginate($request->integer('per_page', 15)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'instansi_id' => [
                'required',
                'integer',
                Rule::exists('pengaduan.instansi', 'id'),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('pengaduan.units', 'id'),
            ],
            'kode' => [
                'required',
                'string',
                'max:50',
                Rule::unique('pengaduan.units', 'kode')
                    ->where('instansi_id', $request->input('instansi_id')),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'aktif' => ['sometimes', 'boolean'],
        ]);

        if (!empty($validated['parent_id'])) {
            $parent = Unit::findOrFail($validated['parent_id']);

            if ((int) $parent->instansi_id !== (int) $validated['instansi_id']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unit induk harus berasal dari instansi yang sama.',
                ], 422);
            }
        }

        $unit = Unit::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Unit berhasil dibuat.',
            'data' => $unit->load(['instansi', 'parent']),
        ], 201);
    }

    public function show(Unit $unit): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Detail unit berhasil diambil.',
            'data' => $unit->load(['instansi', 'parent', 'children']),
        ]);
    }

    public function update(Request $request, Unit $unit): JsonResponse
    {
        $validated = $request->validate([
            'instansi_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('pengaduan.instansi', 'id'),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('pengaduan.units', 'id'),
            ],
            'kode' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('pengaduan.units', 'kode')
                    ->where('instansi_id', $request->input(
                        'instansi_id',
                        $unit->instansi_id
                    ))
                    ->ignore($unit->id),
            ],
            'nama' => ['sometimes', 'required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'aktif' => ['sometimes', 'boolean'],
        ]);

        $instansiId = (int) ($validated['instansi_id'] ?? $unit->instansi_id);
        $parentId = $validated['parent_id'] ?? null;

        if ($parentId !== null) {
            if ((int) $parentId === (int) $unit->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unit tidak boleh menjadi induk bagi dirinya sendiri.',
                ], 422);
            }

            $parent = Unit::findOrFail($parentId);

            if ((int) $parent->instansi_id !== $instansiId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unit induk harus berasal dari instansi yang sama.',
                ], 422);
            }
        }

        $unit->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Unit berhasil diperbarui.',
            'data' => $unit->fresh()->load(['instansi', 'parent']),
        ]);
    }

    public function destroy(Unit $unit): JsonResponse
    {
        if ($unit->children()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unit tidak dapat dinonaktifkan karena masih memiliki unit turunan.',
            ], 422);
        }

        DB::connection('pengaduan')
            ->table('units')
            ->where('id', $unit->id)
            ->update([
                'aktif' => false,
                'updated_at' => now(),
            ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Unit berhasil dinonaktifkan.',
        ]);
    }
}