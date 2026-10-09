<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instansi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InstansiController extends Controller
{
    public function index()
    {
        return response()->json([
            'message' => 'Daftar instansi berhasil diambil.',
            'data' => Instansi::query()
                ->orderBy('nama')
                ->paginate(20),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'singkatan' => ['nullable', 'string', 'max:50'],
            'alamat' => ['nullable', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'aktif' => ['sometimes', 'boolean'],
        ]);

        $instansi = Instansi::create($validated);

        return response()->json([
            'message' => 'Instansi berhasil dibuat.',
            'data' => $instansi,
        ], 201);
    }

    public function show(Instansi $instansi)
    {
        return response()->json([
            'data' => $instansi->load('units'),
        ]);
    }

    public function update(Request $request, Instansi $instansi)
    {
        $validated = $request->validate([
            'nama' => ['sometimes', 'required', 'string', 'max:255'],
            'singkatan' => ['sometimes', 'nullable', 'string', 'max:50'],
            'alamat' => ['sometimes', 'nullable', 'string'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'telepon' => ['sometimes', 'nullable', 'string', 'max:30'],
            'aktif' => ['sometimes', 'boolean'],
        ]);

        $instansi->update($validated);

        return response()->json([
            'message' => 'Instansi berhasil diperbarui.',
            'data' => $instansi->fresh(),
        ]);
    }

    public function destroy(Instansi $instansi)
    {
        if ($instansi->units()->exists()) {
            return response()->json([
                'message' => 'Instansi tidak dapat dihapus karena masih memiliki unit.',
            ], 422);
        }

        $instansi->delete();

        return response()->json([
            'message' => 'Instansi berhasil dihapus.',
        ]);
    }
}