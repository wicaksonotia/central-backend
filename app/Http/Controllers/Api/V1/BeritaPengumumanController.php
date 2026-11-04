<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BeritaPengumuman;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BeritaPengumumanController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | PUBLIC: Daftar berita dan pengumuman
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $validated = $request->validate([
            'instansi_id' => ['nullable', 'integer'],
            'jenis' => ['nullable', Rule::in(['berita', 'pengumuman'])],
            'search' => ['nullable', 'string', 'max:200'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = BeritaPengumuman::query()
            ->published()
            ->with('instansi:id,nama')
            ->latest('published_at');

        if (!empty($validated['instansi_id'])) {
            $query->where('instansi_id', $validated['instansi_id']);
        }

        if (!empty($validated['jenis'])) {
            $query->where('jenis', $validated['jenis']);
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];

            $query->where(function ($q) use ($search) {
                $q->where('judul', 'ilike', "%{$search}%")
                    ->orWhere('ringkasan', 'ilike', "%{$search}%");
            });
        }

        $data = $query->paginate(
            $validated['per_page'] ?? 10
        );

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil berita dan pengumuman.',
            'data' => $data,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLIC: Detail berita dan pengumuman
    |--------------------------------------------------------------------------
    */

    public function show(int $id)
    {
        $data = BeritaPengumuman::query()
            ->published()
            ->with('instansi:id,nama')
            ->findOrFail($id);

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil detail artikel.',
            'data' => $data,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN: Daftar artikel termasuk draft
    |--------------------------------------------------------------------------
    */

    public function adminIndex(Request $request)
    {
        $access = $this->getManagementAccess($request->user());

        if (!$access) {
            return $this->forbidden();
        }

        $validated = $request->validate([
            'instansi_id' => ['nullable', 'integer'],
            'jenis' => ['nullable', Rule::in(['berita', 'pengumuman'])],
            'status' => [
                'nullable',
                Rule::in(['draft', 'published', 'archived']),
            ],
            'search' => ['nullable', 'string', 'max:200'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = BeritaPengumuman::query()
            ->with('instansi:id,nama')
            ->latest();

        if (!$access['all']) {
            $query->whereIn('instansi_id', $access['instansi_ids']);
        }

        if (!empty($validated['instansi_id'])) {
            $query->where('instansi_id', $validated['instansi_id']);
        }

        if (!empty($validated['jenis'])) {
            $query->where('jenis', $validated['jenis']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];

            $query->where(function ($q) use ($search) {
                $q->where('judul', 'ilike', "%{$search}%")
                    ->orWhere('ringkasan', 'ilike', "%{$search}%");
            });
        }

        $userId = (int) $request->user()->getKey();

        $data = $query->paginate(
            $validated['per_page'] ?? 10
        );

        $data->getCollection()->transform(function ($article) use ($userId) {
            $isOwner = (int) $article->created_by === $userId;

            $article->setAttribute('can_edit', $isOwner);
            $article->setAttribute('can_delete', $isOwner);

            return $article;
        });

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil data artikel.',
            'data' => $data,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN: Membuat artikel
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $access = $this->getManagementAccess($request->user());

        if (!$access) {
            return $this->forbidden();
        }

        $validated = $request->validate([
            'instansi_id' => [
                'required',
                'integer',
                'exists:pengaduan.instansi,id',
            ],
            'jenis' => [
                'required',
                Rule::in(['berita', 'pengumuman']),
            ],
            'judul' => ['required', 'string', 'max:200'],
            'ringkasan' => ['nullable', 'string', 'max:500'],
            'konten' => ['required', 'string'],
            'gambar' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'status' => [
                'required',
                Rule::in(['draft', 'published', 'archived']),
            ],
            'published_at' => ['nullable', 'date'],
        ]);

        if (!$this->canManageInstansi($access, $validated['instansi_id'])) {
            return $this->forbidden();
        }

        $gambar = null;

        if ($request->hasFile('gambar')) {
            $gambar = $request->file('gambar')
                ->store('berita-pengumuman', 'public');
        }

        $status = $validated['status'];

        $data = BeritaPengumuman::create([
            'instansi_id' => $validated['instansi_id'],
            'jenis' => $validated['jenis'],
            'judul' => $validated['judul'],
            'slug' => $this->generateSlug(
                $validated['judul'],
                $validated['instansi_id']
            ),
            'ringkasan' => $validated['ringkasan'] ?? null,
            'konten' => $validated['konten'],
            'gambar' => $gambar,
            'status' => $status,
            'published_at' => $status === 'published'
                ? ($validated['published_at'] ?? now())
                : null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Artikel berhasil dibuat.',
            'data' => $data->load('instansi:id,nama'),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN: Mengubah artikel
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, int $id)
    {
        $access = $this->getManagementAccess($request->user());

        if (!$access) {
            return $this->forbidden();
        }

        $article = BeritaPengumuman::findOrFail($id);

        // Pastikan admin memiliki akses ke instansi artikel.
        if (!$this->canManageInstansi($access, $article->instansi_id)) {
            return $this->forbidden();
        }

        // Hanya pembuat artikel yang boleh memperbaruinya.
        if ((int) $article->created_by !== (int) $request->user()->getKey()) {
            return response()->json([
                'status' => false,
                'message' => 'Anda tidak memiliki izin untuk memperbarui artikel ini.',
            ], 403);
        }

        $validated = $request->validate([
            'jenis' => [
                'sometimes',
                Rule::in(['berita', 'pengumuman']),
            ],
            'judul' => ['sometimes', 'required', 'string', 'max:200'],
            'ringkasan' => ['sometimes', 'nullable', 'string', 'max:500'],
            'konten' => ['sometimes', 'required', 'string'],
            'gambar' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'status' => [
                'sometimes',
                Rule::in(['draft', 'published', 'archived']),
            ],
            'published_at' => ['sometimes', 'nullable', 'date'],
        ]);

        $oldImage = $article->gambar;

        if (isset($validated['judul'])) {
            $article->judul = $validated['judul'];

            $article->slug = $this->generateSlug(
                $validated['judul'],
                $article->instansi_id,
                $article->id
            );
        }

        if (array_key_exists('jenis', $validated)) {
            $article->jenis = $validated['jenis'];
        }

        if (array_key_exists('ringkasan', $validated)) {
            $article->ringkasan = $validated['ringkasan'];
        }

        if (array_key_exists('konten', $validated)) {
            $article->konten = $validated['konten'];
        }

        if (array_key_exists('status', $validated)) {
            $article->status = $validated['status'];
        }

        if (array_key_exists('published_at', $validated)) {
            $article->published_at = $validated['published_at'];
        }

        if ($article->status === 'published' && !$article->published_at) {
            $article->published_at = now();
        }

        if ($article->status === 'draft') {
            $article->published_at = null;
        }

        if ($request->hasFile('gambar')) {
            $article->gambar = $request->file('gambar')
                ->store('berita-pengumuman', 'public');
        }

        $article->updated_by = $request->user()->getKey();
        $article->save();

        if ($oldImage && $oldImage !== $article->gambar) {
            Storage::disk('public')->delete($oldImage);
        }

        return response()->json([
            'status' => true,
            'message' => 'Artikel berhasil diperbarui.',
            'data' => $article->fresh()->load('instansi:id,nama'),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | ADMIN: Soft delete artikel
    |--------------------------------------------------------------------------
    */

    public function destroy(Request $request, int $id)
    {
        $access = $this->getManagementAccess($request->user());

        if (!$access) {
            return $this->forbidden();
        }

        $article = BeritaPengumuman::findOrFail($id);

        // Pastikan admin memiliki akses ke instansi artikel.
        if (!$this->canManageInstansi($access, $article->instansi_id)) {
            return $this->forbidden();
        }

        // Hanya pembuat artikel yang boleh menghapusnya.
        if ((int) $article->created_by !== (int) $request->user()->getKey()) {
            return response()->json([
                'status' => false,
                'message' => 'Anda tidak memiliki izin untuk menghapus artikel ini.',
            ], 403);
        }

        $article->delete();

        return response()->json([
            'status' => true,
            'message' => 'Artikel berhasil dihapus.',
            'data' => null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESS CONTROL
    |--------------------------------------------------------------------------
    */

    private function getManagementAccess(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        $userId = $user->getKey();

        $roles = DB::connection('pengaduan')
            ->table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->leftJoin('units', 'units.id', '=', 'user_roles.unit_id')
            ->where('user_roles.user_id', $userId)
            ->select(
                'roles.kode',
                'units.instansi_id'
            )
            ->get();

        // Main Admin dapat mengelola semua instansi.
        if ($roles->contains('kode', 'main_admin')) {
            return [
                'all' => true,
                'instansi_ids' => [],
            ];
        }

        // Unit Admin hanya dapat mengelola instansinya.
        $instansiIds = $roles
            ->where('kode', 'unit_admin')
            ->pluck('instansi_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($instansiIds)) {
            return null;
        }

        return [
            'all' => false,
            'instansi_ids' => $instansiIds,
        ];
    }

    private function canManageInstansi(
        array $access,
        int $instansiId
    ): bool {
        if ($access['all']) {
            return true;
        }

        return in_array(
            $instansiId,
            $access['instansi_ids'],
            true
        );
    }

    private function forbidden()
    {
        return response()->json([
            'status' => false,
            'message' => 'Anda tidak memiliki akses untuk mengelola artikel.',
            'data' => null,
        ], 403);
    }

    /*
    |--------------------------------------------------------------------------
    | SLUG GENERATOR
    |--------------------------------------------------------------------------
    */

    private function generateSlug(
        string $judul,
        int $instansiId,
        ?int $exceptId = null
    ): string {
        $baseSlug = Str::slug($judul);

        if (!$baseSlug) {
            $baseSlug = 'artikel';
        }

        $slug = $baseSlug;
        $counter = 2;

        while (
            BeritaPengumuman::withTrashed()
            ->where('instansi_id', $instansiId)
            ->where('slug', $slug)
            ->when(
                $exceptId,
                fn($query) => $query->where('id', '!=', $exceptId)
            )
            ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
