<?php

namespace App\Http\Controllers\Api\V1\Field;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintAssignment;
use App\Models\ComplaintEvidencePhoto;
use App\Models\ComplaintResponse;
use App\Models\ComplaintStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FieldAssignmentController extends Controller
{
    /**
     * Pastikan pengguna memiliki role field_officer.
     */
    private function ensureFieldOfficer(Request $request): void
    {
        $isFieldOfficer = DB::connection('pengaduan')
            ->table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $request->user()->id)
            ->where('roles.kode', 'field_officer')
            ->exists();

        abort_unless(
            $isFieldOfficer,
            403,
            'Akses hanya untuk Field Officer.'
        );
    }

    /**
     * Pastikan Field Officer terdaftar pada unit pengaduan.
     */
    private function ensureFieldOfficerForUnit(
        int $userId,
        int $unitId
    ): void {
        $isFieldOfficer = DB::connection('pengaduan')
            ->table('user_roles')
            ->join(
                'roles',
                'roles.id',
                '=',
                'user_roles.role_id'
            )
            ->where('user_roles.user_id', $userId)
            ->where('user_roles.unit_id', $unitId)
            ->where('roles.kode', 'field_officer')
            ->exists();

        abort_unless(
            $isFieldOfficer,
            403,
            'Anda tidak terdaftar sebagai Field Officer pada unit pengaduan ini.'
        );
    }


    /**
     * Daftar tugas milik Field Officer yang login.
     */
    public function index(Request $request)
    {
        $this->ensureFieldOfficer($request);

        $assignments = ComplaintAssignment::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'complaint.user',
                'complaint.category',
                'complaint.unit',
                'complaint.photos',
                'assignedBy',
            ])
            ->orderByDesc('ditugaskan_pada')
            ->paginate(15);

        $assignments->getCollection()->transform(
            function ($assignment) {
                if ($assignment->complaint) {
                    $assignment->complaint->photos->transform(
                        function ($photo) {
                            $photo->url = \Illuminate\Support\Facades\Storage::url(
                                $photo->path
                            );

                            return $photo;
                        }
                    );
                }

                return $assignment;
            }
        );

        return response()->json([
            'message' => 'Daftar tugas Field Officer berhasil diambil.',
            'data' => $assignments,
        ]);
    }

    /**
     * Detail tugas berdasarkan ID penugasan.
     */
    public function show(Request $request, $id)
    {
        $this->ensureFieldOfficer($request);

        $assignment = ComplaintAssignment::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'complaint.user',
                'complaint.category',
                'complaint.unit',
                'complaint.photos',
                'complaint.statusHistories.user',
                'complaint.responses.user',
                'assignedBy',
            ])
            ->findOrFail($id);

        if ($assignment->complaint) {
            $assignment->complaint->photos->transform(
                function ($photo) {
                    $photo->url = \Illuminate\Support\Facades\Storage::url(
                        $photo->path
                    );

                    return $photo;
                }
            );
        }

        return response()->json([
            'message' => 'Detail tugas berhasil diambil.',
            'data' => $assignment,
        ]);
    }


    /**
     * Field Officer mengirim hasil penanganan dan foto bukti.
     */
    public function submit(Request $request, $id)
    {
        $this->ensureFieldOfficer($request);

        $validated = $request->validate([
            'catatan' => ['required', 'string', 'max:10000'],
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
            $result = DB::connection('pengaduan')->transaction(
                function () use ($request, $id, $validated, &$storedPaths) {
                    $assignment = ComplaintAssignment::query()
                        ->where('user_id', $request->user()->id)
                        ->whereKey($id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($assignment->selesai_pada !== null) {
                        abort(422, 'Tugas ini sudah dikirim atau diselesaikan.');
                    }

                    $complaint = Complaint::query()
                        ->whereKey($assignment->complaint_id)
                        ->lockForUpdate()
                        ->firstOrFail();
                        
                    $this->ensureFieldOfficerForUnit(
                        (int) $request->user()->id,
                        (int) $complaint->unit_id
                    );

                    if ($complaint->status !== 'diproses') {
                        abort(
                            422,
                            'Pengaduan tidak berada dalam status diproses.'
                        );
                    }

                    // Simpan laporan sebagai catatan internal terlebih dahulu.
                    $response = ComplaintResponse::create([
                        'complaint_id' => $complaint->id,
                        'user_id' => $request->user()->id,
                        'isi' => $validated['catatan'],
                        'untuk_publik' => false,
                    ]);

                    // Simpan foto bukti secara terpisah dari foto awal aduan.
                    foreach ($request->file('photos') as $photo) {
                        $path = $photo->store(
                            'complaint-evidence/' . $complaint->id,
                            'public'
                        );

                        if (! $path) {
                            throw new \RuntimeException(
                                'Gagal menyimpan foto bukti.'
                            );
                        }

                        $storedPaths[] = $path;

                        ComplaintEvidencePhoto::create([
                            'complaint_id' => $complaint->id,
                            'assignment_id' => $assignment->id,
                            'user_id' => $request->user()->id,
                            'path' => $path,
                            'nama_file' => $photo->getClientOriginalName(),
                            'mime_type' => $photo->getMimeType(),
                            'ukuran' => $photo->getSize(),
                        ]);
                    }

                    $previousStatus = $complaint->status;

                    $complaint->status = 'menunggu_pemeriksaan_unit';
                    $complaint->save();

                    $assignment->selesai_pada = now();
                    $assignment->save();

                    ComplaintStatusHistory::create([
                        'complaint_id' => $complaint->id,
                        'user_id' => $request->user()->id,
                        'status_sebelumnya' => $previousStatus,
                        'status_baru' => 'menunggu_pemeriksaan_unit',
                        'catatan' => 'Field Officer mengirim hasil penanganan.',
                        'berubah_pada' => now(),
                    ]);

                    return [
                        'assignment_id' => $assignment->id,
                        'complaint_id' => $complaint->id,
                        'status' => 'menunggu_pemeriksaan_unit',
                        'laporan' => $response,
                    ];
                }
            );

            return response()->json([
                'message' => 'Hasil penanganan berhasil dikirim ke Admin Unit.',
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            // File tidak ikut rollback dalam transaksi database.
            // Hapus file yang sudah terlanjur tersimpan jika proses gagal.
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $e;
        }
    }
}
