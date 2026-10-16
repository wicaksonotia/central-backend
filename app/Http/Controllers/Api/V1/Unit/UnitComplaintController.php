<?php

namespace App\Http\Controllers\Api\V1\Unit;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintAssignment;
use App\Models\ComplaintResponse;
use App\Models\ComplaintStatusHistory;
use App\Models\ComplaintVerification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UnitComplaintController extends Controller
{
    /**
     * Mengambil ID unit yang menjadi kewenangan Admin Unit.
     */
    private function getUnitIds(Request $request): array
    {
        $unitIds = DB::connection('pengaduan')
            ->table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $request->user()->id)
            ->where('roles.kode', 'unit_admin')
            ->whereNotNull('user_roles.unit_id')
            ->pluck('user_roles.unit_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        abort_if(
            empty($unitIds),
            403,
            'Akun ini tidak memiliki peran Admin Unit.'
        );

        return $unitIds;
    }

    /**
     * Daftar pengaduan yang ditugaskan ke unit milik pengguna.
     */
    public function index(Request $request): JsonResponse
    {
        $unitIds = $this->getUnitIds($request);

        $query = Complaint::query()
            ->whereIn('unit_id', $unitIds)
            ->with([
                'user:id,name,email,phone',
                'category',
                'unit',
                'photos',
            ])
            ->latest();

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->string('status')->toString()
            );
        }

        $complaints = $query->paginate(15);

        $complaints->getCollection()->transform(
            fn(Complaint $complaint) =>
            $this->appendPhotoUrls($complaint)
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar pengaduan unit berhasil diambil.',
            'data' => $complaints,
        ]);
    }

    /**
     * Detail pengaduan yang menjadi kewenangan unit.
     */
    public function show(
        Request $request,
        string $id
    ): JsonResponse {
        $unitIds = $this->getUnitIds($request);

        $complaint = Complaint::query()
            ->whereIn('unit_id', $unitIds)
            ->with([
                'user:id,name,email,phone',
                'category',
                'unit',
                'photos',

                'statusHistories.user:id,name',
                'responses.user:id,name',
                'verifications.user:id,name',

                // Riwayat penugasan petugas lapangan
                'assignments.user:id,name,email,phone',
                'assignments.assignedBy:id,name',

                // Foto bukti penanganan
                'assignments.evidencePhotos',
                'assignments.evidencePhotos.user:id,name',
            ])
            ->findOrFail($id);

        // URL foto awal pengaduan
        $this->appendPhotoUrls($complaint);

        // URL foto bukti penanganan petugas
        foreach ($complaint->assignments as $assignment) {
            foreach ($assignment->evidencePhotos as $photo) {
                $photo->url = Storage::url($photo->path);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Detail pengaduan berhasil diambil.',
            'data' => $complaint,
        ]);
    }

    /**
     * Mengembalikan pengaduan yang salah unit kepada Admin Utama.
     */
    public function returnComplaint(
        Request $request,
        string $id
    ): JsonResponse {
        $unitIds = $this->getUnitIds($request);

        $validated = $request->validate([
            'alasan' => [
                'required',
                'string',
                'min:5',
                'max:2000',
            ],
        ]);

        $db = DB::connection('pengaduan');

        $complaint = $db->transaction(function () use (
            $id,
            $unitIds,
            $validated,
            $request
        ) {
            $complaint = Complaint::query()
                ->whereIn('unit_id', $unitIds)
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array(
                $complaint->status,
                ['ditugaskan', 'diproses'],
                true
            )) {
                throw ValidationException::withMessages([
                    'status' => [
                        'Pengaduan hanya dapat dikembalikan saat berstatus ditugaskan atau diproses.',
                    ],
                ]);
            }

            // Jangan mengembalikan pengaduan yang masih ditangani
            // oleh Field Officer.
            $activeAssignment = ComplaintAssignment::query()
                ->where('complaint_id', $complaint->id)
                ->whereNull('selesai_pada')
                ->exists();

            if ($activeAssignment) {
                throw ValidationException::withMessages([
                    'pengaduan' => [
                        'Pengaduan masih memiliki penugasan aktif kepada Field Officer. Selesaikan atau cabut penugasan tersebut terlebih dahulu.',
                    ],
                ]);
            }

            $statusSebelumnya = $complaint->status;
            $unitSebelumnya = $complaint->unit;
            $namaUnit = $unitSebelumnya?->nama ?? 'unit sebelumnya';

            // Kembalikan pengaduan ke antrean Admin Utama.
            $complaint->unit_id = null;
            $complaint->status = 'dikembalikan';
            $complaint->save();

            ComplaintStatusHistory::create([
                'complaint_id' => $complaint->id,
                'user_id' => $request->user()->id,
                'status_sebelumnya' => $statusSebelumnya,
                'status_baru' => 'dikembalikan',
                'catatan' => 'Pengaduan dikembalikan oleh '
                    . $namaUnit . '. Alasan: ' . $validated['alasan'],
                'berubah_pada' => now(),
            ]);

            return $complaint->fresh()->load([
                'user:id,name,email,phone',
                'category',
                'unit',
                'photos',
                'statusHistories.user:id,name',
            ]);
        });

        foreach ($complaint->photos as $photo) {
            $photo->url = Storage::url($photo->path);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pengaduan berhasil dikembalikan kepada Admin Utama.',
            'data' => $complaint,
        ]);
    }

    /**
     * Menambahkan tanggapan atau catatan tindak lanjut.
     */
    public function storeResponse(
        Request $request,
        string $id
    ): JsonResponse {
        $unitIds = $this->getUnitIds($request);

        $validated = $request->validate([
            'isi' => ['required', 'string', 'max:10000'],
            'untuk_publik' => ['sometimes', 'boolean'],
        ]);

        $complaint = Complaint::query()
            ->whereIn('unit_id', $unitIds)
            ->findOrFail($id);

        if (in_array(
            $complaint->status,
            ['menunggu_verifikasi', 'selesai', 'ditutup'],
            true
        )) {
            throw ValidationException::withMessages([
                'status' => [
                    'Pengaduan sedang menunggu verifikasi atau sudah ditutup.',
                ],
            ]);
        }

        $response = ComplaintResponse::create([
            'complaint_id' => $complaint->id,
            'user_id' => $request->user()->id,
            'isi' => $validated['isi'],
            'untuk_publik' => $validated['untuk_publik'] ?? false,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Tanggapan berhasil disimpan.',
            'data' => $response->load('user:id,name'),
        ], 201);
    }

    /**
     * Menambahkan URL akses foto pada respons API.
     */
    private function appendPhotoUrls(
        Complaint $complaint
    ): Complaint {
        $complaint->photos->each(function ($photo) {
            $photo->setAttribute(
                'url',
                Storage::url($photo->path)
            );
        });

        return $complaint;
    }

    /**
     * Menugaskan pengaduan kepada Field Officer
     * yang terdaftar pada unit yang sama dengan pengaduan.
     */
    public function assignFieldOfficer(
        Request $request,
        string $id
    ): JsonResponse {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'min:1'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);

        // Pastikan pengguna yang login adalah Admin Unit.
        $unitIds = $this->getUnitIds($request);

        $db = DB::connection('pengaduan');

        try {
            $result = $db->transaction(function () use (
                $id,
                $validated,
                $unitIds,
                $request,
                $db
            ) {
                // Kunci pengaduan dan pastikan pengaduan
                // termasuk unit yang dikelola Admin Unit.
                $complaint = Complaint::query()
                    ->whereIn('unit_id', $unitIds)
                    ->whereKey($id)
                    ->lockForUpdate()
                    ->first();

                if (! $complaint) {
                    abort(
                        404,
                        'Pengaduan tidak ditemukan pada unit Anda.'
                    );
                }

                if (! in_array(
                    $complaint->status,
                    ['ditugaskan', 'diproses'],
                    true
                )) {
                    abort(
                        422,
                        'Pengaduan belum dapat ditugaskan pada status saat ini.'
                    );
                }

                // Petugas wajib terdaftar pada unit yang sama
                // dengan pengaduan yang akan ditugaskan.
                $fieldOfficerValid = $db->table('user_roles')
                    ->join(
                        'roles',
                        'roles.id',
                        '=',
                        'user_roles.role_id'
                    )
                    ->where(
                        'user_roles.user_id',
                        $validated['user_id']
                    )
                    ->where(
                        'roles.kode',
                        'field_officer'
                    )
                    ->where(
                        'user_roles.unit_id',
                        $complaint->unit_id
                    )
                    ->exists();

                if (! $fieldOfficerValid) {
                    throw ValidationException::withMessages([
                        'user_id' => [
                            'Field Officer harus terdaftar pada unit yang sama dengan pengaduan.',
                        ],
                    ]);
                }

                // Cegah penugasan baru jika masih ada
                // penugasan aktif pada pengaduan ini.
                $activeAssignment = ComplaintAssignment::query()
                    ->where('complaint_id', $complaint->id)
                    ->whereNull('selesai_pada')
                    ->exists();

                if ($activeAssignment) {
                    throw ValidationException::withMessages([
                        'pengaduan' => [
                            'Pengaduan masih memiliki penugasan aktif.',
                        ],
                    ]);
                }

                $previousStatus = $complaint->status;

                $assignment = ComplaintAssignment::create([
                    'complaint_id' => $complaint->id,
                    'user_id' => $validated['user_id'],
                    'assigned_by' => $request->user()->id,
                    'catatan' => $validated['catatan'] ?? null,
                    'ditugaskan_pada' => now(),
                    'selesai_pada' => null,
                ]);

                if ($previousStatus !== 'diproses') {
                    $complaint->status = 'diproses';
                    $complaint->save();

                    ComplaintStatusHistory::create([
                        'complaint_id' => $complaint->id,
                        'user_id' => $request->user()->id,
                        'status_sebelumnya' => $previousStatus,
                        'status_baru' => 'diproses',
                        'catatan' =>
                        'Pengaduan ditugaskan kepada Field Officer.',
                        'berubah_pada' => now(),
                    ]);
                }

                return [
                    'assignment' => $assignment->load([
                        'user',
                        'assignedBy',
                        'complaint',
                    ]),
                    'complaint' => $complaint->fresh(),
                ];
            });

            return response()->json([
                'status' => 'success',
                'message' =>
                'Pengaduan berhasil ditugaskan kepada Field Officer.',
                'data' => $result,
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'message' =>
                'Terjadi kesalahan saat menugaskan Field Officer.',
            ], 500);
        }
    }


    /**
     * Admin Unit memeriksa hasil penanganan Field Officer.
     */
    public function reviewAssignment(
        Request $request,
        string $id
    ): JsonResponse {
        $unitIds = $this->getUnitIds($request);

        $validated = $request->validate([
            'keputusan' => [
                'required',
                'in:disetujui,perlu_perbaikan',
            ],
            'catatan' => [
                'required_if:keputusan,perlu_perbaikan',
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $db = DB::connection('pengaduan');

        $result = $db->transaction(function () use (
            $id,
            $unitIds,
            $validated,
            $request
        ) {
            // Ambil penugasan dan kunci barisnya.
            $assignment = ComplaintAssignment::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            // Pastikan pengaduan termasuk unit yang dikelola
            // oleh Admin Unit yang sedang login.
            $complaint = Complaint::query()
                ->whereKey($assignment->complaint_id)
                ->whereIn('unit_id', $unitIds)
                ->lockForUpdate()
                ->firstOrFail();

            if ($complaint->status !== 'menunggu_pemeriksaan_unit') {
                throw ValidationException::withMessages([
                    'status' => [
                        'Pengaduan belum menunggu pemeriksaan Admin Unit atau sudah diperiksa.',
                    ],
                ]);
            }

            if ($assignment->selesai_pada === null) {
                throw ValidationException::withMessages([
                    'assignment' => [
                        'Field Officer belum mengirim hasil penanganan.',
                    ],
                ]);
            }

            // Pastikan penugasan yang diperiksa memang milik
            // pengaduan tersebut dan merupakan penugasan terakhir.
            $latestAssignmentId = ComplaintAssignment::query()
                ->where('complaint_id', $complaint->id)
                ->orderByDesc('ditugaskan_pada')
                ->orderByDesc('id')
                ->value('id');

            if ((int) $latestAssignmentId !== (int) $assignment->id) {
                throw ValidationException::withMessages([
                    'assignment' => [
                        'Penugasan ini bukan penugasan terbaru untuk pengaduan tersebut.',
                    ],
                ]);
            }

            $decision = $validated['keputusan'];
            $previousStatus = $complaint->status;

            $verification = ComplaintVerification::create([
                'complaint_id' => $complaint->id,
                'user_id' => $request->user()->id,
                'tahap' => 'admin_unit',
                'keputusan' => $decision,
                'catatan' => $validated['catatan'] ?? null,
                'diverifikasi_pada' => now(),
            ]);

            if ($decision === 'disetujui') {
                $newStatus = 'menunggu_verifikasi';

                $statusNote =
                    'Hasil Field Officer disetujui Admin Unit '
                    . 'dan diteruskan ke Admin Utama.';
            } else {
                $newStatus = 'diproses';

                // Buka kembali penugasan agar Field Officer
                // dapat memperbaiki dan mengirim ulang hasilnya.
                $assignment->selesai_pada = null;
                $assignment->save();

                $statusNote =
                    'Hasil Field Officer perlu diperbaiki: '
                    . $validated['catatan'];
            }

            $complaint->status = $newStatus;
            $complaint->save();

            ComplaintStatusHistory::create([
                'complaint_id' => $complaint->id,
                'user_id' => $request->user()->id,
                'status_sebelumnya' => $previousStatus,
                'status_baru' => $newStatus,
                'catatan' => $statusNote,
                'berubah_pada' => now(),
            ]);

            return [
                'complaint_id' => $complaint->id,
                'assignment_id' => $assignment->id,
                'keputusan' => $decision,
                'status' => $newStatus,
                'verification' => $verification,
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => $validated['keputusan'] === 'disetujui'
                ? 'Hasil penanganan disetujui dan diteruskan ke Admin Utama.'
                : 'Hasil penanganan dikembalikan kepada Field Officer.',
            'data' => $result,
        ]);
    }


    /**
     * Daftar Field Officer pada unit tertentu.
     * Unit harus berada dalam kewenangan Admin Unit yang login.
     */
    public function fieldOfficers(Request $request): JsonResponse
    {
        $unitIds = $this->getUnitIds($request);

        $validated = $request->validate([
            'unit_id' => [
                'required',
                'integer',
                'min:1',
                'in:' . implode(',', $unitIds),
            ],
        ]);

        $unitId = (int) $validated['unit_id'];

        $officers = DB::connection('pengaduan')
            ->table('user_roles')
            ->join(
                'roles',
                'roles.id',
                '=',
                'user_roles.role_id'
            )
            ->join(
                'users',
                'users.id',
                '=',
                'user_roles.user_id'
            )
            ->where('roles.kode', 'field_officer')
            ->where('user_roles.unit_id', $unitId)
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.phone',
                'user_roles.unit_id',
            ])
            ->distinct()
            ->orderBy('users.name')
            ->get();

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar Field Officer berhasil diambil.',
            'data' => $officers,
        ]);
    }
}
