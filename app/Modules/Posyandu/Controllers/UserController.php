<?php

namespace App\Modules\Posyandu\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Posyandu\Models\User;
use App\Modules\Posyandu\Services\UserService;
use App\Modules\Posyandu\Services\PetugasContextService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected PetugasContextService $petugasContext
    ) {}

    /**
     * Daftar kader berdasarkan Posyandu Admin.
     */
    public function index(Request $request)
    {
        $posyanduId = $this->petugasContext
            ->adminPosyanduId($request->user('posyandu'));

        $validated = $request->validate([
            'status' => ['sometimes', 'in:aktif,nonaktif,semua'],
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $status = $validated['status'] ?? 'aktif';
        $search = $validated['search'] ?? null;
        $perPage = $validated['per_page'] ?? 15;

        $query = User::query()
            ->whereHas('petugasPosyandu', function ($q) use (
                $posyanduId,
                $status
            ) {
                $q->where('posyandu_id', $posyanduId)
                    ->where('peran', 'kader');

                if ($status === 'aktif') {
                    $q->where('status', true);
                } elseif ($status === 'nonaktif') {
                    $q->where('status', false);
                }
            })
            ->with([
                'petugasPosyandu' => function ($q) use ($posyanduId) {
                    $q->where('posyandu_id', $posyanduId)
                        ->where('peran', 'kader')
                        ->with([
                            'penugasanPos.posyanduPos.posPemeriksaan',
                        ]);
                },
            ]);

        // Pencarian nama atau nomor HP.
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('phone', 'ILIKE', "%{$search}%");
            });
        }

        $users = $query
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar kader berhasil diambil.',
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * Membuat akun kader.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'posyandu_pos_ids' => ['sometimes', 'array', 'min:1'],
            'posyandu_pos_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $user = $this->userService->createKader(
            $request->user('posyandu'),
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Akun kader berhasil dibuat.',
            'data' => $user,
        ], 201);
    }

    /**
     * Detail kader.
     */
    public function show(Request $request, int $userId)
    {
        $posyanduId = $this->petugasContext
            ->adminPosyanduId($request->user('posyandu'));

        $user = User::query()
            ->with([
                'petugasPosyandu' => function ($query) use ($posyanduId) {
                    $query->where('posyandu_id', $posyanduId)
                        ->where('peran', 'kader')
                        ->where('status', true)
                        ->with([
                            'penugasanPos.posyanduPos.posPemeriksaan',
                        ]);
                },
            ])
            ->whereHas('petugasPosyandu', function ($query) use ($posyanduId) {
                $query->where('posyandu_id', $posyanduId)
                    ->where('status', true)
                    ->where('peran', 'kader');
            })
            ->findOrFail($userId);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Update data kader.
     */
    public function update(Request $request, int $userId)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:20'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'posyandu_pos_ids' => ['sometimes', 'array', 'min:1'],
            'posyandu_pos_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $user = $this->userService->updateKader(
            $request->user('posyandu'),
            $userId,
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Data kader berhasil diperbarui.',
            'data' => $user,
        ]);
    }

    /**
     * Nonaktifkan kader.
     */
    public function destroy(Request $request, int $userId)
    {
        $this->userService->deactivateKader(
            $request->user('posyandu'),
            $userId
        );

        return response()->json([
            'success' => true,
            'message' => 'Kader berhasil dinonaktifkan.',
        ]);
    }

    /**
     * Mengaktifkan kembali kader.
     */
    public function activate(Request $request, int $userId)
    {
        $validated = $request->validate([
            'posyandu_pos_ids' => ['sometimes', 'array', 'min:1'],
            'posyandu_pos_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $user = $this->userService->activateKader(
            $request->user('posyandu'),
            $userId,
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Kader berhasil diaktifkan kembali.',
            'data' => $user,
        ]);
    }
}
