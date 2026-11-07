<?php

namespace App\Http\Controllers\Api\V1\Unit;

use App\Http\Controllers\Controller;
use App\Models\UserRole;
use App\Services\UserManagementService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitFieldOfficerController extends Controller
{
    protected UserManagementService $userService;

    public function __construct(UserManagementService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Membuat beberapa Field Officer untuk unit Admin Unit yang login.
     */
    public function store(Request $request)
    {
        $unitId = $this->getAdminUnitId($request);

        abort_if(!$unitId, 403, 'Unit Admin tidak ditemukan.');

        $validated = $request->validate([
            'users' => ['required', 'array', 'min:1'],
            'users.*.name' => ['required', 'string', 'max:255'],
            'users.*.phone' => [
                'required',
                'string',
                'max:20',
                'distinct',
                Rule::unique('pengaduan.users', 'phone'),
            ],
        ]);

        $users = $this->userService->createBulk(
            'field_officer',
            $unitId,
            $validated['users']
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Field Officer berhasil ditambahkan.',
            'data' => [
                'users' => $users,
                'total' => $users->count(),
            ],
        ], 201);
    }

    /**
     * Mengambil unit_id Admin Unit yang sedang login.
     */
    private function getAdminUnitId(Request $request): ?int
    {
        $admin = $request->user();

        if (!$admin || !$admin->hasRole('unit_admin')) {
            return null;
        }

        $unitId = $admin->userRoles()
            ->whereHas('role', function ($query) {
                $query->where('kode', 'unit_admin');
            })
            ->value('unit_id');

        return $unitId ? (int) $unitId : null;
    }

    /**
     * Query dasar Field Officer pada unit tertentu.
     */
    private function fieldOfficerQuery(int $unitId)
    {
        return UserRole::query()
            ->with([
                'user:id,name,phone,email,avatar,created_at',
                'role:id,kode,nama',
                'unit:id,kode,nama',
            ])
            ->where('unit_id', $unitId)
            ->whereHas('role', function ($query) {
                $query->where('kode', 'field_officer');
            });
    }

    /**
     * Daftar Field Officer pada unit Admin Unit yang login.
     */
    public function index(Request $request)
    {
        $unitId = $this->getAdminUnitId($request);

        if (!$unitId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unit Admin tidak ditemukan atau akses ditolak.',
            ], 403);
        }

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => [
                'nullable',
                'string',
                Rule::in(['active', 'inactive']),
            ],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        // Default menampilkan Field Officer aktif.
        $status = $validated['status'] ?? 'active';

        // Query sudah dibatasi berdasarkan unit Admin Unit yang login.
        $query = $this->fieldOfficerQuery($unitId);

        // Filter status penugasan.
        if ($status === 'active') {
            $query->active();
        } else {
            $query->inactive();
        }

        // Pencarian nama dan nomor telepon.
        if (!empty($validated['q'])) {
            $search = $validated['q'];

            $query->whereHas('user', function ($userQuery) use ($search) {
                $userQuery->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('phone', 'ILIKE', "%{$search}%");
                });
            });
        }

        $users = $query
            ->orderBy('user_id')
            ->orderByDesc('created_at')
            ->paginate($validated['per_page'] ?? 15)
            ->through(function (UserRole $userRole) {
                return [
                    // ID user tetap dipertahankan.
                    'id' => $userRole->user->id,

                    // ID penugasan untuk aktivasi kembali.
                    'user_role_id' => $userRole->id,

                    'name' => $userRole->user->name,
                    'phone' => $userRole->user->phone,
                    'email' => $userRole->user->email,
                    'avatar' => $userRole->user->avatar,

                    'role_kode' => $userRole->role->kode,
                    'role_nama' => $userRole->role->nama,

                    'unit_id' => $userRole->unit?->id,
                    'unit_kode' => $userRole->unit?->kode,
                    'unit_nama' => $userRole->unit?->nama,

                    'is_active' => $userRole->is_active,
                    'assigned_at' => $userRole->created_at,
                    'deactivated_at' => $userRole->deactivated_at,
                ];
            });

        return response()->json([
            'status' => 'success',
            'message' => 'Data Field Officer berhasil diambil.',
            'data' => $users,
        ]);
    }

    /**
     * Detail Field Officer pada unit Admin Unit yang login.
     */
    public function show(Request $request, int $id)
    {
        $unitId = $this->getAdminUnitId($request);

        if (!$unitId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unit Admin tidak ditemukan atau akses ditolak.',
            ], 403);
        }

        $userRole = $this->fieldOfficerQuery($unitId)
            ->where('user_id', $id)
            ->first();

        if (!$userRole) {
            return response()->json([
                'status' => 'error',
                'message' => 'Field Officer tidak ditemukan pada unit Anda.',
            ], 404);
        }

        $user = $userRole->user;

        return response()->json([
            'status' => 'success',
            'message' => 'Detail Field Officer berhasil diambil.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'created_at' => $user->created_at,

                'role_kode' => $userRole->role->kode,
                'role_nama' => $userRole->role->nama,

                'unit_id' => $userRole->unit?->id,
                'unit_kode' => $userRole->unit?->kode,
                'unit_nama' => $userRole->unit?->nama,

                'assigned_at' => $userRole->created_at,
            ],
        ]);
    }

    /**
     * Memperbarui Field Officer pada unit Admin Unit yang login.
     */
    public function update(Request $request, int $id)
    {
        $unitId = $this->getAdminUnitId($request);

        if (!$unitId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unit Admin tidak ditemukan atau akses ditolak.',
            ], 403);
        }

        $userRole = $this->fieldOfficerQuery($unitId)
            ->where('user_id', $id)
            ->first();

        if (!$userRole) {
            return response()->json([
                'status' => 'error',
                'message' => 'Field Officer tidak ditemukan pada unit Anda.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'max:20',
                Rule::unique('pengaduan.users', 'phone')
                    ->ignore($id),
            ],
        ]);

        $user = $this->userService->updateUser(
            $userRole->user,
            $validated
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Data Field Officer berhasil diperbarui.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'role_kode' => $userRole->role->kode,
                'role_nama' => $userRole->role->nama,
                'unit_id' => $userRole->unit_id,
            ],
        ]);
    }

    /**
     * Menonaktifkan Field Officer pada unit Admin Unit yang login.
     */
    public function deactivate(Request $request, int $id)
    {
        $unitId = $this->getAdminUnitId($request);

        if (!$unitId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unit Admin tidak ditemukan atau akses ditolak.',
            ], 403);
        }

        $userRole = $this->fieldOfficerQuery($unitId)
            ->active()
            ->whereKey($id)
            ->first();

        if (!$userRole) {
            return response()->json([
                'status' => 'error',
                'message' => 'Field Officer aktif tidak ditemukan pada unit Anda.',
            ], 404);
        }

        $success = $this->userService->deactivateUserRole($userRole);

        if (!$success) {
            return response()->json([
                'status' => 'error',
                'message' => 'Field Officer gagal dinonaktifkan.',
            ], 409);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Field Officer berhasil dinonaktifkan.',
        ]);
    }

    /**
     * Mengaktifkan kembali Field Officer di unit Admin Unit.
     *
     * Parameter $id adalah ID user_roles.
     */
    public function reactivate(Request $request, int $id)
    {
        $unitId = $this->getAdminUnitId($request);

        if (!$unitId) {
            return response()->json([
                'success' => false,
                'message' => 'Unit Admin tidak ditemukan.',
            ], 403);
        }

        // Hanya mengambil penugasan Field Officer nonaktif
        // yang berasal dari unit Admin Unit tersebut.
        $userRole = UserRole::query()
            ->inactive()
            ->with([
                'user:id,name,phone,email,avatar',
                'role:id,kode,nama',
                'unit:id,kode,nama',
            ])
            ->where('unit_id', $unitId)
            ->whereHas('role', function ($query) {
                $query->where('kode', 'field_officer');
            })
            ->find($id);

        if (!$userRole) {
            return response()->json([
                'success' => false,
                'message' => 'Riwayat penugasan Field Officer tidak ditemukan di unit Anda.',
            ], 404);
        }

        $success = $this->userService->reactivateUserRole($userRole);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'User sudah memiliki role aktif atau penugasan tidak dapat diaktifkan.',
            ], 409);
        }

        $userRole->refresh()->load([
            'user:id,name,phone,email,avatar',
            'role:id,kode,nama',
            'unit:id,kode,nama',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Field Officer berhasil diaktifkan kembali.',
            'data' => [
                'user_role_id' => $userRole->id,
                'user_id' => $userRole->user_id,
                'name' => $userRole->user->name,
                'phone' => $userRole->user->phone,
                'role_kode' => $userRole->role->kode,
                'role_nama' => $userRole->role->nama,
                'unit_id' => $userRole->unit_id,
                'unit_nama' => $userRole->unit?->nama,
                'is_active' => $userRole->is_active,
            ],
        ]);
    }
}
