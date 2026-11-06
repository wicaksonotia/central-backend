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
    /**
     * Instansi yang digunakan saat ini.
     * Sementara aplikasi hanya melayani satu instansi.
     */
    private const INSTANSI_ID = 1;

    /**
     * Daftar unit.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Unit::with([
            'instansi:id,nama,singkatan',
            'parent:id,nama',
        ])->where('instansi_id', self::INSTANSI_ID);

        if ($request->filled('aktif')) {
            $query->where('aktif', $request->boolean('aktif'));
        }

        if ($request->filled('q')) {
            $keyword = trim($request->input('q'));

            $query->where(function ($q) use ($keyword) {
                $q->where('nama', 'ilike', "%{$keyword}%")
                    ->orWhere('kode', 'ilike', "%{$keyword}%");
            });
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar unit berhasil diambil.',
            'data' => $query
                ->orderBy('nama')
                ->paginate($request->integer('per_page', 15)),
        ]);
    }

    /**
     * Tambah unit.
     */
    public function store(Request $request): JsonResponse
    {
        $instansiId = self::INSTANSI_ID;

        $validated = $request->validate([
            'kode' => [
                'required',
                'string',
                'max:50',
                Rule::unique('pengaduan.units', 'kode')
                    ->where('instansi_id', $instansiId),
            ],
            'nama' => [
                'required',
                'string',
                'max:255',
            ],
            'deskripsi' => [
                'nullable',
                'string',
            ],
            'alamat' => [
                'nullable',
                'string',
                'max:255',
            ],
            'telepon' => [
                'nullable',
                'string',
                'max:30',
            ],
            'aktif' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $validated['instansi_id'] = $instansiId;
        $validated['parent_id'] = null;

        $unit = DB::connection('pengaduan')->transaction(
            function () use ($validated) {
                return Unit::create($validated);
            }
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Unit berhasil dibuat.',
            'data' => $unit->load('instansi'),
        ], 201);
    }

    /**
     * Detail unit.
     */
    public function show(Unit $unit): JsonResponse
    {
        if ((int) $unit->instansi_id !== self::INSTANSI_ID) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unit tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Detail unit berhasil diambil.',
            'data' => $unit->load([
                'instansi',
                'parent',
                'children',
            ]),
        ]);
    }

    /**
     * Perbarui unit.
     */
    public function update(Request $request, Unit $unit): JsonResponse
    {
        if ((int) $unit->instansi_id !== self::INSTANSI_ID) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unit tidak ditemukan.',
            ], 404);
        }

        $instansiId = (int) $unit->instansi_id;

        $validated = $request->validate([
            'parent_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('pengaduan.units', 'id')
                    ->where('instansi_id', $instansiId),
            ],
            'kode' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('pengaduan.units', 'kode')
                    ->where('instansi_id', $instansiId)
                    ->ignore($unit->id),
            ],
            'nama' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'deskripsi' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'alamat' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'telepon' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
            ],
            'aktif' => [
                'sometimes',
                'boolean',
            ],
        ]);

        // Jika parent_id tidak dikirim, gunakan parent lama.
        $parentId = array_key_exists('parent_id', $validated)
            ? $validated['parent_id']
            : $unit->parent_id;

        if ($parentId !== null) {
            if ((int) $parentId === (int) $unit->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unit tidak boleh menjadi induk bagi dirinya sendiri.',
                ], 422);
            }

            $parent = Unit::where('instansi_id', $instansiId)
                ->find($parentId);

            if (!$parent) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unit induk tidak ditemukan.',
                ], 422);
            }
        }

        DB::connection('pengaduan')->transaction(function () use ($unit, $validated) {
            $unit->update($validated);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Unit berhasil diperbarui.',
            'data' => $unit->fresh()->load([
                'instansi',
                'parent',
            ]),
        ]);
    }

    /**
     * Nonaktifkan unit.
     */
    public function destroy(Unit $unit): JsonResponse
    {
        if ((int) $unit->instansi_id !== self::INSTANSI_ID) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unit tidak ditemukan.',
            ], 404);
        }

        if ($unit->children()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unit tidak dapat dinonaktifkan karena masih memiliki unit turunan.',
            ], 422);
        }

        DB::connection('pengaduan')->transaction(function () use ($unit) {
            $unit->update([
                'aktif' => false,
            ]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Unit berhasil dinonaktifkan.',
        ]);
    }
}
