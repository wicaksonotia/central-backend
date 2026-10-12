<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintStatusHistory;
use App\Models\ComplaintVerification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminVerificationController extends Controller
{
    /**
     * Daftar pengaduan yang menunggu keputusan Admin Utama.
     */
    public function index(): JsonResponse
    {
        $complaints = Complaint::query()
            ->where('status', 'menunggu_verifikasi')
            ->with([
                'user:id,name,email,phone',
                'category',
                'unit',
                'photos',
                'responses.user:id,name',
                'assignments.user:id,name,email,phone',
                'assignments.assignedBy:id,name',
                'assignments.evidencePhotos',
                'assignments.evidencePhotos.user:id,name',
                'verifications.user:id,name',
            ])
            ->latest('updated_at')
            ->paginate(15);

        $complaints->getCollection()->each(
            fn ($complaint) => $this->appendPhotoUrls($complaint)
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar pengaduan yang menunggu verifikasi berhasil diambil.',
            'data' => $complaints,
        ]);
    }

    /**
     * Menyetujui atau menolak hasil penanganan unit.
     */
    public function verify(
        Request $request,
        string $id
    ): JsonResponse {
        $validated = $request->validate([
            'keputusan' => [
                'required',
                'string',
                Rule::in(['disetujui', 'ditolak']),
            ],
            'catatan' => [
                Rule::requiredIf(
                    fn () => $request->input('keputusan') === 'ditolak'
                ),
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $result = DB::connection('pengaduan')->transaction(
            function () use ($request, $validated, $id) {
                $complaint = Complaint::query()
                    ->lockForUpdate()
                    ->findOrFail($id);

                if ($complaint->status !== 'menunggu_verifikasi') {
                    throw ValidationException::withMessages([
                        'status' => [
                            'Pengaduan ini tidak sedang menunggu verifikasi.',
                        ],
                    ]);
                }

                $now = now();
                $statusSebelumnya = $complaint->status;
                $disetujui = $validated['keputusan'] === 'disetujui';

                $statusBaru = $disetujui
                    ? 'ditutup'
                    : 'ditugaskan';

                $catatan = $validated['catatan']
                    ?? 'Hasil penanganan disetujui oleh Admin Utama.';

                // Simpan keputusan Admin Utama.
                $verification = ComplaintVerification::create([
                    'complaint_id' => $complaint->id,
                    'user_id' => $request->user()->id,
                    'tahap' => 'admin_utama',
                    'keputusan' => $validated['keputusan'],
                    'catatan' => $catatan,
                    'diverifikasi_pada' => $now,
                ]);

                // Perbarui status pengaduan.
                $complaint->status = $statusBaru;

                if ($disetujui) {
                    $complaint->selesai_pada ??= $now;
                    $complaint->ditutup_pada = $now;
                } else {
                    // Kembalikan pengaduan ke Admin Unit.
                    // Assignment lama tidak diaktifkan kembali.
                    $complaint->ditutup_pada = null;
                }

                $complaint->save();

                // Catat perubahan status dan alasan keputusan.
                ComplaintStatusHistory::create([
                    'complaint_id' => $complaint->id,
                    'user_id' => $request->user()->id,
                    'status_sebelumnya' => $statusSebelumnya,
                    'status_baru' => $statusBaru,
                    'catatan' => $catatan,
                    'berubah_pada' => $now,
                ]);

                return [
                    'complaint' => $complaint->load([
                        'user:id,name,email,phone',
                        'category',
                        'unit',
                        'photos',
                        'responses.user:id,name',
                        'assignments.user:id,name,email,phone',
                        'assignments.assignedBy:id,name',
                        'assignments.evidencePhotos',
                        'verifications.user:id,name',
                        'statusHistories.user:id,name',
                    ]),
                    'verification' => $verification,
                ];
            }
        );

        $complaint = $result['complaint'];

        $this->appendPhotoUrls($complaint);

        return response()->json([
            'status' => 'success',
            'message' => $validated['keputusan'] === 'disetujui'
                ? 'Hasil penanganan disetujui. Pengaduan telah ditutup.'
                : 'Hasil penanganan ditolak. Pengaduan dikembalikan ke Admin Unit untuk ditindaklanjuti.',
            'data' => $result,
        ]);
    }

    /**
     * Menambahkan URL foto pengaduan dan foto bukti penanganan.
     */
    private function appendPhotoUrls(Complaint $complaint): void
    {
        foreach ($complaint->photos as $photo) {
            $photo->url = Storage::url($photo->path);
        }

        foreach ($complaint->assignments as $assignment) {
            foreach ($assignment->evidencePhotos as $photo) {
                $photo->url = Storage::url($photo->path);
            }
        }
    }
}