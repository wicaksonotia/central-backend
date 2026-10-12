<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\ComplaintPhoto;
use App\Models\ComplaintStatusHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ComplaintController extends Controller
{
    /**
     * Daftar pengaduan milik pengguna yang sedang login.
     */
    public function index(Request $request): JsonResponse
    {
        $complaints = Complaint::query()
            ->where('user_id', $request->user()->id)
            ->with(['category', 'unit', 'photos'])
            ->latest()
            ->paginate(15);

        $complaints->getCollection()->transform(
            fn(Complaint $complaint) =>
            $this->appendPhotoUrls($complaint)
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar pengaduan berhasil diambil.',
            'data' => $complaints,
        ]);
    }

    /**
     * Membuat pengaduan baru beserta foto dan riwayat status.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'isi_pengaduan' => ['required', 'string'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'alamat' => ['nullable', 'string', 'max:1000'],

            // Foto dikirim sebagai multipart/form-data.
            'photos' => ['required', 'array', 'min:1', 'max:5'],
            'photos.*' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        $storedPaths = [];

        try {
            $complaint = DB::connection('pengaduan')
                ->transaction(function () use (
                    $request,
                    $validated,
                    &$storedPaths
                ) {
                    $complaint = Complaint::create([
                        'nomor_pengaduan' => $this->generateComplaintNumber(),
                        'user_id' => $request->user()->id,
                        'judul' => $validated['judul'],
                        'isi_pengaduan' => $validated['isi_pengaduan'],
                        'status' => 'diajukan',
                        'latitude' => $validated['latitude'],
                        'longitude' => $validated['longitude'],
                        'alamat' => $validated['alamat'] ?? null,
                        'diajukan_pada' => now(),
                    ]);

                    foreach ($request->file('photos', []) as $file) {
                        $path = $file->store(
                            'complaints/' . $complaint->id,
                            'public'
                        );

                        if (!$path) {
                            throw new \RuntimeException(
                                'Foto pengaduan gagal disimpan.'
                            );
                        }

                        $storedPaths[] = $path;

                        ComplaintPhoto::create([
                            'complaint_id' => $complaint->id,
                            'path' => $path,
                            'nama_file' => $file->getClientOriginalName(),
                            'mime_type' => $file->getMimeType(),
                            'ukuran' => $file->getSize(),
                        ]);
                    }

                    ComplaintStatusHistory::create([
                        'complaint_id' => $complaint->id,
                        'user_id' => $request->user()->id,
                        'status_sebelumnya' => null,
                        'status_baru' => 'diajukan',
                        'catatan' => 'Pengaduan dibuat oleh masyarakat.',
                        'berubah_pada' => now(),
                    ]);

                    return $complaint;
                });

            $complaint->load([
                'category',
                'unit',
                'photos',
                'statusHistories',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Pengaduan berhasil dikirim.',
                'data' => $this->appendPhotoUrls($complaint),
            ], 201);
        } catch (Throwable $e) {
            // Database transaction tidak menghapus file secara otomatis.
            if (!empty($storedPaths)) {
                Storage::disk('public')->delete($storedPaths);
            }

            throw $e;
        }
    }

    /**
     * Detail pengaduan.
     * Pengguna hanya dapat melihat pengaduannya sendiri.
     */
    public function show(
        Request $request,
        string $id
    ): JsonResponse {
        $complaint = Complaint::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'category',
                'unit',
                'photos',
                'statusHistories',
                'responses' => function ($query) {
                    $query->where('untuk_publik', true);
                },
            ])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'message' => 'Detail pengaduan berhasil diambil.',
            'data' => $this->appendPhotoUrls($complaint),
        ]);
    }

    /**
     * Membuat nomor pengaduan yang unik.
     */
    private function generateComplaintNumber(): string
    {
        do {
            $number = 'ADU-' . now()->format('Ymd')
                . '-' . Str::upper(Str::random(6));

            $exists = Complaint::query()
                ->where('nomor_pengaduan', $number)
                ->exists();
        } while ($exists);

        return $number;
    }

    /**
     * Menambahkan URL foto pada respons API.
     */
    private function appendPhotoUrls(Complaint $complaint): Complaint
    {
        $complaint->photos->each(function (ComplaintPhoto $photo) {
            $photo->setAttribute(
                'url',
                Storage::url($photo->path)
            );
        });

        return $complaint;
    }
}
