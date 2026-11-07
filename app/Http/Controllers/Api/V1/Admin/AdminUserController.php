<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserRole;
use App\Services\UserManagementService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    protected UserManagementService $userService;

    public function __construct(UserManagementService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Membuat beberapa Admin Unit sekaligus.
     */
    public function storeUnitAdmins(Request $request)
    {
        $validated = $request->validate([
            'unit_id' => [
                'required',
                'integer',
                Rule::exists('pengaduan.units', 'id'),
            ],
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
            'unit_admin',
            (int) $validated['unit_id'],
            $validated['users']
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Admin Unit berhasil ditambahkan.',
            'data' => [
                'users' => $users,
                'total' => $users->count(),
            ],
        ], 201);
    }

    /**
     * Membuat beberapa Field Officer sekaligus.
     */
    public function storeFieldOfficers(Request $request)
    {
        $validated = $request->validate([
            'unit_id' => [
                'required',
                'integer',
                Rule::exists('pengaduan.units', 'id'),
            ],
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
            (int) $validated['unit_id'],
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
     * Daftar Admin Unit dan Field Officer.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => [
                'nullable',
                'string',
                Rule::in(['unit_admin', 'field_officer']),
            ],
            'unit_id' => [
                'nullable',
                'integer',
                Rule::exists('pengaduan.units', 'id'),
            ],
            'status' => [
                'nullable',
                'string',
                Rule::in(['active', 'inactive']),
            ],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        // Default menampilkan penugasan aktif.
        $status = $validated['status'] ?? 'active';

        $query = UserRole::query()
            ->with([
                'user:id,name,phone,email,avatar,created_at',
                'role:id,kode,nama',
                'unit:id,kode,nama',
            ])
            ->whereHas('role', function ($query) {
                $query->whereIn('kode', [
                    'unit_admin',
                    'field_officer',
                ]);
            });

        // Filter status penugasan.
        if ($status === 'active') {
            $query->active();
        } else {
            $query->inactive();
        }

        // Pencarian nama, nomor telepon, atau nama unit.
        if (!empty($validated['q'])) {
            $search = $validated['q'];

            $query->where(function ($query) use ($search) {
                $query->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery
                        ->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('phone', 'ILIKE', "%{$search}%");
                })->orWhereHas('unit', function ($unitQuery) use ($search) {
                    $unitQuery->where('nama', 'ILIKE', "%{$search}%");
                });
            });
        }

        // Filter berdasarkan role.
        if (!empty($validated['role'])) {
            $query->whereHas('role', function ($roleQuery) use ($validated) {
                $roleQuery->where('kode', $validated['role']);
            });
        }

        // Filter berdasarkan unit.
        if (!empty($validated['unit_id'])) {
            $query->where('unit_id', $validated['unit_id']);
        }

        // Pengurutan menggunakan subquery Eloquent.
        $query
            ->orderBy(
                Unit::query()
                    ->select('nama')
                    ->whereColumn('units.id', 'user_roles.unit_id')
            )
            ->orderBy(
                Role::query()
                    ->select('kode')
                    ->whereColumn('roles.id', 'user_roles.role_id')
            )
            ->orderBy(
                User::query()
                    ->select('name')
                    ->whereColumn('users.id', 'user_roles.user_id')
            )
            ->orderByDesc('user_roles.created_at');

        $users = $query
            ->paginate($validated['per_page'] ?? 15)
            ->through(function (UserRole $userRole) {
                return [
                    // ID user tetap dipertahankan untuk kompatibilitas Flutter.
                    'id' => $userRole->user->id,

                    // ID penugasan untuk kebutuhan aktivasi kembali.
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
            'message' => 'Data pengguna berhasil diambil.',
            'data' => $users,
        ]);
    }

    /**
     * Detail Admin Unit atau Field Officer.
     */
    public function show(int $id)
    {
        $userRole = UserRole::query()
            ->with([
                'user:id,name,phone,email,avatar,created_at',
                'role:id,kode,nama',
                'unit:id,kode,nama',
            ])
            ->where('user_id', $id)
            ->whereHas('role', function ($query) {
                $query->whereIn('kode', [
                    'unit_admin',
                    'field_officer',
                ]);
            })
            ->first();

        if (!$userRole) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data pengguna tidak ditemukan.',
            ], 404);
        }

        $user = $userRole->user;

        return response()->json([
            'status' => 'success',
            'message' => 'Detail pengguna berhasil diambil.',
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
     * Memperbarui Admin Unit atau Field Officer.
     */
    public function update(Request $request, int $id)
    {
        $userRole = UserRole::query()
            ->with(['user', 'role'])
            ->where('user_id', $id)
            ->whereHas('role', function ($query) {
                $query->whereIn('kode', [
                    'unit_admin',
                    'field_officer',
                ]);
            })
            ->first();

        if (!$userRole) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data pengguna tidak ditemukan.',
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
            'message' => 'Data pengguna berhasil diperbarui.',
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
     * Menonaktifkan penugasan Admin Unit atau Field Officer.
     */
    public function deactivate(int $id)
    {
        $userRole = UserRole::query()
            ->active()
            ->whereKey($id)
            ->whereHas('role', function ($query) {
                $query->whereIn('kode', [
                    'unit_admin',
                    'field_officer',
                ]);
            })
            ->first();

        if (!$userRole) {
            return response()->json([
                'status' => 'error',
                'message' => 'Penugasan aktif tidak ditemukan.',
            ], 404);
        }

        $success = $this->userService->deactivateUserRole($userRole);

        if (!$success) {
            return response()->json([
                'status' => 'error',
                'message' => 'Penugasan gagal dinonaktifkan.',
            ], 409);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Penugasan berhasil dinonaktifkan.',
        ]);
    }

    /**
     * Mengaktifkan kembali penugasan user.
     *
     * Parameter $id adalah ID user_roles.
     */
    public function reactivate(int $id)
    {
        $userRole = UserRole::query()
            ->inactive()
            ->with([
                'user:id,name,phone,email,avatar',
                'role:id,kode,nama',
                'unit:id,kode,nama',
            ])
            ->whereHas('role', function ($query) {
                $query->whereIn('kode', [
                    'unit_admin',
                    'field_officer',
                ]);
            })
            ->find($id);

        if (!$userRole) {
            return response()->json([
                'success' => false,
                'message' => 'Riwayat penugasan tidak ditemukan atau bukan penugasan yang dapat diaktifkan.',
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
            'message' => 'User berhasil diaktifkan kembali.',
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

    /**
     * Rolling atau perubahan penugasan user.
     *
     * Hanya dapat dilakukan oleh Admin Utama.
     */
    public function transfer(Request $request, int $id)
    {
        $validated = $request->validate([
            'role_kode' => [
                'required',
                'string',
                Rule::in([
                    'unit_admin',
                    'field_officer',
                ]),
            ],
            'unit_id' => [
                'required',
                'integer',
                Rule::exists('pengaduan.units', 'id'),
            ],
        ]);

        try {
            $userRole = $this->userService->transferUserRole(
                $id,
                $validated['role_kode'],
                $validated['unit_id']
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Penugasan user berhasil diperbarui.',
                'data' => [
                    'user_role_id' => $userRole->id,
                    'user_id' => $userRole->user_id,
                    'name' => $userRole->user->name,
                    'phone' => $userRole->user->phone,
                    'email' => $userRole->user->email,
                    'role_kode' => $userRole->role->kode,
                    'role_nama' => $userRole->role->nama,
                    'unit_id' => $userRole->unit_id,
                    'unit_kode' => $userRole->unit->kode,
                    'unit_nama' => $userRole->unit->nama,
                    'is_active' => $userRole->is_active,
                    'assigned_at' => $userRole->created_at,
                ],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
