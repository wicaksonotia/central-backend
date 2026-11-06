<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
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
}