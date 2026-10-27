<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PDO;
use PDOException;
use Throwable;

class BukuController extends Controller
{
    private const PESAN_AMAN = 'Terjadi kesalahan pada sistem. Silakan coba lagi nanti.';

    private function pdo(): PDO
    {
        return DB::connection()->getPdo();
    }

    private function daftarKategori(): array
    {
        $stmt = $this->pdo()->prepare('SELECT id, nama_kategori FROM kategori_buku ORDER BY nama_kategori');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /** Sanitasi: trim + strip_tags */
    private function bersihkan(Request $request): array
    {
        $data = [];
        foreach (['kode_buku', 'judul', 'isbn', 'tahun_terbit', 'kategori_buku_id'] as $f) {
            $data[$f] = trim(strip_tags((string) $request->input($f, '')));
        }
        return $data;
    }

    private function validasi(array $data, int $ignoreId = 0)
    {
        $v = Validator::make($data, [
            'kode_buku'        => ['required', 'size:10', 'alpha_num'],
            'judul'            => ['required', 'min:3', 'max:100'],
            'isbn'             => ['required', 'digits:13'],
            'tahun_terbit'     => ['required', 'integer', 'between:2000,2026'],
            'kategori_buku_id' => ['required', 'integer'],
        ], [
            'kode_buku.size'        => 'Kode buku harus tepat 10 karakter.',
            'kode_buku.alpha_num'   => 'Kode buku hanya boleh huruf dan angka.',
            'judul.min'             => 'Judul minimal 3 karakter.',
            'judul.max'             => 'Judul maksimal 100 karakter.',
            'isbn.digits'           => 'ISBN harus tepat 13 digit angka.',
            'tahun_terbit.between'  => 'Tahun terbit harus antara 2000 dan 2026.',
            'required'              => ':attribute wajib diisi.',
        ]);

        $v->after(function ($v) use ($data, $ignoreId) {
            $pdo = $this->pdo();

            if (! $v->errors()->has('kode_buku')) {
                $s = $pdo->prepare('SELECT COUNT(*) FROM buku WHERE kode_buku = :nilai AND id <> :id');
                $s->execute([':nilai' => $data['kode_buku'], ':id' => $ignoreId]);
                if ((int) $s->fetchColumn() > 0) {
                    $v->errors()->add('kode_buku', 'Kode buku sudah digunakan.');
                }
            }

            if (! $v->errors()->has('isbn')) {
                $s = $pdo->prepare('SELECT COUNT(*) FROM buku WHERE isbn = :nilai AND id <> :id');
                $s->execute([':nilai' => $data['isbn'], ':id' => $ignoreId]);
                if ((int) $s->fetchColumn() > 0) {
                    $v->errors()->add('isbn', 'ISBN sudah digunakan.');
                }
            }

            if (! $v->errors()->has('kategori_buku_id')) {
                $s = $pdo->prepare('SELECT COUNT(*) FROM kategori_buku WHERE id = :id');
                $s->execute([':id' => (int) $data['kategori_buku_id']]);
                if ((int) $s->fetchColumn() === 0) {
                    $v->errors()->add('kategori_buku_id', 'Kategori tidak tersedia.');
                }
            }
        });

        return $v;
    }

    // GET /buku  (daftar + pencarian + filter)
    public function index(Request $request)
    {
        $q = trim(strip_tags((string) $request->query('q', '')));
        $kat = (string) $request->query('kategori_buku_id', '');
        $kat = ctype_digit($kat) ? (int) $kat : 0;

        try {
            $sql = 'SELECT b.id, b.kode_buku, b.judul, b.isbn, b.tahun_terbit, k.nama_kategori
                    FROM buku b
                    JOIN kategori_buku k ON k.id = b.kategori_buku_id
                    WHERE 1 = 1';
            $params = [];

            if ($q !== '') {
                $sql .= ' AND (b.kode_buku LIKE :q1 OR b.judul LIKE :q2 OR b.isbn LIKE :q3)';
                $like = '%' . $q . '%';
                $params[':q1'] = $like;
                $params[':q2'] = $like;
                $params[':q3'] = $like;
            }
            if ($kat > 0) {
                $sql .= ' AND b.kategori_buku_id = :kat';
                $params[':kat'] = $kat;
            }
            $sql .= ' ORDER BY b.id ASC';

            $stmt = $this->pdo()->prepare($sql);
            $stmt->execute($params);

            return view('buku.index', [
                'bukuList'     => $stmt->fetchAll(PDO::FETCH_OBJ),
                'kategoriList' => $this->daftarKategori(),
                'q'            => $q,
                'kat'          => $kat,
            ]);
        } catch (Throwable $e) {
            report($e);
            return view('buku.index', [
                'bukuList'     => [],
                'kategoriList' => [],
                'q'            => $q,
                'kat'          => $kat,
            ])->with('error', self::PESAN_AMAN);
        }
    }

    // POST /buku
    public function store(Request $request)
    {
        $data = $this->bersihkan($request);

        try {
            $v = $this->validasi($data);
            if ($v->fails()) {
                return redirect()->route('buku.index')->withErrors($v)->withInput($data);
            }

            $stmt = $this->pdo()->prepare(
                'INSERT INTO buku (kode_buku, judul, isbn, tahun_terbit, kategori_buku_id, created_at, updated_at)
                 VALUES (:kode, :judul, :isbn, :tahun, :kat, NOW(), NOW())'
            );
            $stmt->execute([
                ':kode'  => $data['kode_buku'],
                ':judul' => $data['judul'],
                ':isbn'  => $data['isbn'],
                ':tahun' => (int) $data['tahun_terbit'],
                ':kat'   => (int) $data['kategori_buku_id'],
            ]);

            return redirect()->route('buku.index')->with('success', 'Buku berhasil ditambahkan.');
        } catch (PDOException $e) {
            report($e);
            $pesan = ($e->getCode() === '23000')
                ? 'Kode buku atau ISBN sudah digunakan.'
                : self::PESAN_AMAN;
            return redirect()->route('buku.index')->withInput($data)->with('error', $pesan);
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('buku.index')->withInput($data)->with('error', self::PESAN_AMAN);
        }
    }

    // GET /buku/{id}/edit
    public function edit(int $id)
    {
        try {
            $stmt = $this->pdo()->prepare(
                'SELECT id, kode_buku, judul, isbn, tahun_terbit, kategori_buku_id FROM buku WHERE id = :id'
            );
            $stmt->execute([':id' => $id]);
            $buku = $stmt->fetch(PDO::FETCH_OBJ);

            if (! $buku) {
                return redirect()->route('buku.index')->with('error', 'Data buku tidak ditemukan.');
            }

            return view('buku.edit', [
                'buku'         => $buku,
                'kategoriList' => $this->daftarKategori(),
            ]);
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('buku.index')->with('error', self::PESAN_AMAN);
        }
    }

    // PUT /buku/{id}
    public function update(Request $request, int $id)
    {
        $data = $this->bersihkan($request);

        try {
            $v = $this->validasi($data, $id);
            if ($v->fails()) {
                return redirect()->route('buku.edit', $id)->withErrors($v)->withInput($data);
            }

            $stmt = $this->pdo()->prepare(
                'UPDATE buku
                 SET kode_buku = :kode, judul = :judul, isbn = :isbn,
                     tahun_terbit = :tahun, kategori_buku_id = :kat, updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                ':kode'  => $data['kode_buku'],
                ':judul' => $data['judul'],
                ':isbn'  => $data['isbn'],
                ':tahun' => (int) $data['tahun_terbit'],
                ':kat'   => (int) $data['kategori_buku_id'],
                ':id'    => $id,
            ]);

            return redirect()->route('buku.index')->with('success', 'Buku berhasil diperbarui.');
        } catch (PDOException $e) {
            report($e);
            $pesan = ($e->getCode() === '23000')
                ? 'Kode buku atau ISBN sudah digunakan.'
                : self::PESAN_AMAN;
            return redirect()->route('buku.edit', $id)->withInput($data)->with('error', $pesan);
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('buku.edit', $id)->withInput($data)->with('error', self::PESAN_AMAN);
        }
    }

    // DELETE /buku/{id}
    public function destroy(int $id)
    {
        try {
            $stmt = $this->pdo()->prepare('DELETE FROM buku WHERE id = :id');
            $stmt->execute([':id' => $id]);

            return redirect()->route('buku.index')->with('success', 'Buku berhasil dihapus.');
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('buku.index')->with('error', self::PESAN_AMAN);
        }
    }
}