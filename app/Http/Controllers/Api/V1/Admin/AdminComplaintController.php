<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\ComplaintStatusHistory;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminComplaintController extends Controller
{
    /**
     * Daftar pengaduan untuk Admin Utama.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Complaint::query()
            ->with([
                'user:id,name,email,phone',
                'category',
                'unit',
                'photos',
            ])
            ->latest();

        // Filter pengaduan yang belum didelegasikan.
        if ($request->boolean('belum_didelegasikan')) {
            $query->whereNull('unit_id');
        }

        // Filter berdasarkan status jika diminta.
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $complaints = $query->paginate(15);

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar pengaduan berhasil diambil.',
            'data' => $complaints,
        ]);
    }

    /**
     * Detail pengaduan.
     */
    public function show(string $id): JsonResponse
    {
        $complaint = Complaint::query()
            ->with([
                'user:id,name,email,phone',
                'category',
                'unit',
                'photos',
                'statusHistories.user:id,name',
                'responses.user:id,name',
                'verifications.user:id,name',
            ])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Detail pengaduan berhasil diambil.',
            'data' => $complaint,
        ]);
    }

    /**
     * Delegasikan pengaduan ke unit.
     */
    public function delegate(
        Request $request,
        string $id
    ): JsonResponse {
        $validated = $request->validate([
            'unit_id' => [
                'required',
                'integer',
                'exists:pengaduan.units,id',
            ],
            'catatan' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $user = $request->user();

        $complaint = DB::connection('pengaduan')->transaction(
            function () use ($validated, $user, $id) {
                $complaint = Complaint::query()
                    ->lockForUpdate()
                    ->findOrFail($id);

                // Hanya pengaduan baru atau yang dikembalikan
                // yang boleh didelegasikan.
                if (! in_array(
                    $complaint->status,
                    ['diajukan', 'dikembalikan'],
                    true
                )) {
                    throw ValidationException::withMessages([
                        'status' => [
                            'Hanya pengaduan baru atau pengaduan yang dikembalikan yang dapat didelegasikan.',
                        ],
                    ]);
                }

                $unit = Unit::query()
                    ->whereKey($validated['unit_id'])
                    ->where('aktif', true)
                    ->first();

                if (! $unit) {
                    throw ValidationException::withMessages([
                        'unit_id' => [
                            'Unit tidak ditemukan atau sedang tidak aktif.',
                        ],
                    ]);
                }

                $statusSebelumnya = $complaint->status;

                $complaint->unit_id = $unit->id;
                $complaint->status = 'ditugaskan';
                $complaint->save();

                ComplaintStatusHistory::create([
                    'complaint_id' => $complaint->id,
                    'user_id' => $user->id,
                    'status_sebelumnya' => $statusSebelumnya,
                    'status_baru' => 'ditugaskan',
                    'catatan' => $validated['catatan']
                        ?? 'Pengaduan didelegasikan ke unit '
                        . $unit->nama . '.',
                    'berubah_pada' => now(),
                ]);

                return $complaint->load([
                    'user:id,name,email,phone',
                    'category',
                    'unit',
                    'photos',
                    'statusHistories',
                ]);
            }
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Pengaduan berhasil didelegasikan ke unit '
                . $complaint->unit->nama . '.',
            'data' => $complaint,
        ]);
    }

    /**
     * Menolak pengaduan masyarakat sebelum didelegasikan.
     */
    public function reject(
        Request $request,
        string $id
    ): JsonResponse {
        $validated = $request->validate([
            'catatan' => [
                'required',
                'string',
                'min:5',
                'max:2000',
            ],
        ]);

        $complaint = DB::connection('pengaduan')->transaction(
            function () use ($request, $validated, $id) {
                $complaint = Complaint::query()
                    ->lockForUpdate()
                    ->findOrFail($id);

                if ($complaint->status !== 'diajukan') {
                    throw ValidationException::withMessages([
                        'status' => [
                            'Hanya pengaduan berstatus diajukan yang dapat ditolak pada pemeriksaan awal.',
                        ],
                    ]);
                }

                $statusSebelumnya = $complaint->status;
                $now = now();

                $complaint->status = 'ditolak';
                $complaint->save();

                ComplaintStatusHistory::create([
                    'complaint_id' => $complaint->id,
                    'user_id' => $request->user()->id,
                    'status_sebelumnya' => $statusSebelumnya,
                    'status_baru' => 'ditolak',
                    'catatan' => $validated['catatan'],
                    'berubah_pada' => $now,
                ]);

                return $complaint->fresh()->load([
                    'user:id,name,email,phone',
                    'category',
                    'unit',
                    'photos',
                    'statusHistories.user:id,name',
                ]);
            }
        );

        foreach ($complaint->photos as $photo) {
            $photo->url = Storage::url($photo->path);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pengaduan ditolak. Masyarakat harus membuat pengaduan baru jika ingin melaporkannya kembali.',
            'data' => $complaint,
        ]);
    }

    public function setCategory(Request $request, int $id)
    {
        $complaint = Complaint::find($id);

        if (!$complaint) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengaduan tidak ditemukan.',
            ], 404);
        }

        if ($complaint->status !== 'diajukan') {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori hanya dapat ditentukan saat pengaduan masih diajukan.',
            ], 422);
        }

        $categoryModel = new ComplaintCategory();

        $validated = $request->validate([
            'category_id' => [
                'required',
                'integer',
                Rule::exists(
                    $categoryModel->getConnectionName() . '.' .
                        $categoryModel->getTable(),
                    'id'
                ),
            ],
        ]);

        // Ambil kategori yang dipilih.
        $category = ComplaintCategory::find($validated['category_id']);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori pengaduan tidak ditemukan.',
            ], 404);
        }

        // Pastikan kategori memiliki unit tujuan.
        if (!$category->unit_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori ini belum memiliki unit tujuan.',
            ], 422);
        }

        // Simpan kategori dan unit tujuan sekaligus.
        $complaint->category_id = $category->id;
        $complaint->unit_id = $category->unit_id;
        $complaint->save();

        $complaint->load(['category', 'unit']);

        return response()->json([
            'status' => 'success',
            'message' => 'Kategori dan unit tujuan pengaduan berhasil ditentukan.',
            'data' => [
                'id' => $complaint->id,
                'nomor_pengaduan' => $complaint->nomor_pengaduan,
                'category_id' => $complaint->category_id,
                'category' => $complaint->category,
                'unit_id' => $complaint->unit_id,
                'unit' => $complaint->unit,
            ],
        ]);
    }
}
