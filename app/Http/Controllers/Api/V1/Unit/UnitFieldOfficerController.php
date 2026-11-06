<?php

namespace App\Http\Controllers\Api\V1\Unit;

use App\Http\Controllers\Controller;
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
        $admin = $request->user();

        // Pastikan akun yang login adalah Admin Unit.
        abort_unless($admin && $admin->hasRole('unit_admin'), 403, 'Akses ditolak.');

        // Ambil unit dari relasi user_roles Admin Unit.
        $unitId = $admin->userRoles()
            ->whereHas('role', function ($query) {
                $query->where('kode', 'unit_admin');
            })
            ->value('unit_id');

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
            (int) $unitId,
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
}