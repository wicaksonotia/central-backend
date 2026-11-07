<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Collection;
use RuntimeException;

class UserManagementService
{
    /**
     * Membuat beberapa user sekaligus beserta role dan unit-nya.
     */
    public function createBulk(
        string $roleCode,
        int $unitId,
        array $users
    ): Collection {
        $connection = (new User())->getConnection();

        return $connection->transaction(function () use (
            $roleCode,
            $unitId,
            $users
        ) {
            $role = Role::query()
                ->where('kode', $roleCode)
                ->firstOrFail();

            $createdUsers = collect();

            foreach ($users as $item) {
                $user = User::create([
                    'name' => trim($item['name']),
                    'phone' => trim($item['phone']),
                ]);

                UserRole::create([
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'unit_id' => $unitId,
                    'is_active' => true,
                    'deactivated_at' => null,
                ]);

                $createdUsers->push([
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'role' => $role->kode,
                    'unit_id' => $unitId,
                    'is_active' => true,
                ]);
            }

            return $createdUsers;
        });
    }

    /**
     * Memperbarui data user.
     */
    public function updateUser(User $user, array $data): User
    {
        return $user->getConnection()->transaction(function () use (
            $user,
            $data
        ) {
            $user->name = trim($data['name']);
            $user->phone = trim($data['phone']);

            $user->save();

            return $user->fresh();
        });
    }

    /**
     * Menonaktifkan penugasan role user.
     * Data user dan riwayat penugasan tetap dipertahankan.
     *
     * Seluruh token Sanctum user dihapus.
     */
    public function deactivateUserRole(UserRole $userRole): bool
    {
        return $userRole->getConnection()->transaction(function () use (
            $userRole
        ) {
            $user = User::query()
                ->whereKey($userRole->user_id)
                ->lockForUpdate()
                ->first();

            if (!$user) {
                return false;
            }

            $activeUserRole = UserRole::query()
                ->whereKey($userRole->id)
                ->where('user_id', $user->id)
                ->active()
                ->lockForUpdate()
                ->first();

            if (!$activeUserRole) {
                return false;
            }

            $activeUserRole->update([
                'is_active' => false,
                'deactivated_at' => now(),
            ]);

            // Hapus seluruh token Sanctum milik user.
            $user->tokens()->delete();

            return true;
        });
    }

    /**
     * Mengaktifkan kembali riwayat penugasan user.
     *
     * Hanya bisa dilakukan jika user belum memiliki role aktif lain.
     */
    public function reactivateUserRole(UserRole $userRole): bool
    {
        return $userRole->getConnection()->transaction(function () use (
            $userRole
        ) {
            $user = User::query()
                ->whereKey($userRole->user_id)
                ->lockForUpdate()
                ->first();

            if (!$user) {
                return false;
            }

            $assignment = UserRole::query()
                ->whereKey($userRole->id)
                ->where('user_id', $user->id)
                ->inactive()
                ->lockForUpdate()
                ->first();

            if (!$assignment) {
                return false;
            }

            $hasActiveRole = UserRole::query()
                ->where('user_id', $user->id)
                ->active()
                ->exists();

            if ($hasActiveRole) {
                return false;
            }

            $assignment->update([
                'is_active' => true,
                'deactivated_at' => null,
            ]);

            return true;
        });
    }

    /**
     * Melakukan rolling atau perubahan penugasan user.
     *
     * Penugasan lama dinonaktifkan dan penugasan baru dibuat
     * dengan user_id yang sama.
     *
     * Seluruh token Sanctum user dihapus.
     */
    public function transferUserRole(
        int $userId,
        string $newRoleCode,
        int $newUnitId
    ): UserRole {
        $connection = (new User())->getConnection();

        return $connection->transaction(function () use (
            $userId,
            $newRoleCode,
            $newUnitId
        ) {
            $user = User::query()
                ->whereKey($userId)
                ->lockForUpdate()
                ->firstOrFail();

            $allowedRoles = [
                'unit_admin',
                'field_officer',
            ];

            if (!in_array($newRoleCode, $allowedRoles, true)) {
                throw new RuntimeException(
                    'Role tujuan tidak valid untuk proses rolling.'
                );
            }

            $newRole = Role::query()
                ->where('kode', $newRoleCode)
                ->first();

            if (!$newRole) {
                throw new RuntimeException(
                    'Role tujuan tidak ditemukan.'
                );
            }

            $newUnit = Unit::query()
                ->find($newUnitId);

            if (!$newUnit) {
                throw new RuntimeException(
                    'Unit tujuan tidak ditemukan.'
                );
            }

            $currentAssignment = UserRole::query()
                ->active()
                ->where('user_id', $user->id)
                ->with('role')
                ->lockForUpdate()
                ->first();

            if (!$currentAssignment) {
                throw new RuntimeException(
                    'User tidak memiliki penugasan aktif.'
                );
            }

            if (
                !$currentAssignment->role ||
                !in_array(
                    $currentAssignment->role->kode,
                    $allowedRoles,
                    true
                )
            ) {
                throw new RuntimeException(
                    'Role saat ini tidak dapat dipindahkan melalui fitur ini.'
                );
            }

            if (
                (int) $currentAssignment->role_id === (int) $newRole->id &&
                (int) $currentAssignment->unit_id === (int) $newUnit->id
            ) {
                throw new RuntimeException(
                    'User sudah memiliki role dan unit tersebut.'
                );
            }

            // Nonaktifkan penugasan lama.
            $currentAssignment->update([
                'is_active' => false,
                'deactivated_at' => now(),
            ]);

            // Buat penugasan baru.
            $newAssignment = UserRole::create([
                'user_id' => $user->id,
                'role_id' => $newRole->id,
                'unit_id' => $newUnit->id,
                'is_active' => true,
                'deactivated_at' => null,
            ]);

            // Hapus seluruh token Sanctum milik user.
            $user->tokens()->delete();

            return $newAssignment->load([
                'user:id,name,phone,email,avatar',
                'role:id,kode,nama',
                'unit:id,kode,nama',
            ]);
        });
    }
}
