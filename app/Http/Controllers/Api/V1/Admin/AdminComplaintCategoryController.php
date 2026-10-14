<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminComplaintCategoryController extends Controller
{
    public function index()
    {
        $categories = ComplaintCategory::query()
            ->with('unit:id,nama,kode')
            ->withCount('complaints')
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar kategori pengaduan berhasil diambil.',
            'data' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'unit_id' => [
                'required',
                'integer',
                Rule::exists('pengaduan.units', 'id'),
            ],
            'nama' => [
                'required',
                'string',
                'max:150',
                'unique:pengaduan.complaint_categories,nama',
            ],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'aktif' => ['sometimes', 'boolean'],
            'urutan' => ['sometimes', 'integer', 'min:0'],
        ]);

        $unit = Unit::find($validated['unit_id']);

        if (!$unit || !$unit->aktif) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori hanya dapat dihubungkan ke unit yang aktif.',
            ], 422);
        }

        $slug = Str::slug($validated['nama']);

        if (ComplaintCategory::where('slug', $slug)->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Slug kategori sudah digunakan. Silakan gunakan nama kategori lain.',
            ], 422);
        }

        $category = ComplaintCategory::create([
            'unit_id' => $unit->id,
            'nama' => $validated['nama'],
            'slug' => $slug,
            'deskripsi' => $validated['deskripsi'] ?? null,
            'aktif' => $validated['aktif'] ?? true,
            'urutan' => $validated['urutan'] ?? 0,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori berhasil ditambahkan.',
            'data' => $category->load('unit:id,nama,kode'),
        ], 201);
    }

    public function show(int $id)
    {
        $category = ComplaintCategory::query()
            ->with('unit:id,nama,kode')
            ->withCount('complaints')
            ->find($id);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Detail kategori berhasil diambil.',
            'data' => $category,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $category = ComplaintCategory::find($id);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'unit_id' => [
                'required',
                'integer',
                Rule::exists('pengaduan.units', 'id'),
            ],
            'nama' => [
                'required',
                'string',
                'max:150',
                Rule::unique(
                    'pengaduan.complaint_categories',
                    'nama'
                )->ignore($category->id),
            ],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'aktif' => ['sometimes', 'boolean'],
            'urutan' => ['sometimes', 'integer', 'min:0'],
        ]);

        $unit = Unit::find($validated['unit_id']);

        if (!$unit || !$unit->aktif) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori hanya dapat dihubungkan ke unit yang aktif.',
            ], 422);
        }

        $slug = Str::slug($validated['nama']);

        $slugExists = ComplaintCategory::where('slug', $slug)
            ->where('id', '!=', $category->id)
            ->exists();

        if ($slugExists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Slug kategori sudah digunakan. Silakan gunakan nama kategori lain.',
            ], 422);
        }

        $category->unit_id = $unit->id;
        $category->nama = $validated['nama'];
        $category->slug = $slug;
        $category->deskripsi = $validated['deskripsi'] ?? null;

        if (array_key_exists('aktif', $validated)) {
            $category->aktif = $validated['aktif'];
        }

        if (array_key_exists('urutan', $validated)) {
            $category->urutan = $validated['urutan'];
        }

        $category->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori berhasil diperbarui.',
            'data' => $category->fresh()->load('unit:id,nama,kode'),
        ]);
    }

    public function destroy(int $id)
    {
        $category = ComplaintCategory::find($id);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori tidak ditemukan.',
            ], 404);
        }

        $isUsed = Complaint::where('category_id', $category->id)
            ->exists();

        if ($isUsed) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori tidak dapat dihapus karena sudah digunakan oleh pengaduan.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori berhasil dihapus.',
        ]);
    }
}
