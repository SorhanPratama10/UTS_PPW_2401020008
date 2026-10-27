<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Buku Perpustakaan</title>
</head>
<body>
    <h1>Data Buku Perpustakaan</h1>

    @if (session('success'))
        <p><strong>Berhasil:</strong> {{ session('success') }}</p>
    @endif
    @if (session('error'))
        <p><strong>Gagal:</strong> {{ session('error') }}</p>
    @endif

    <h2>Tambah Buku</h2>
    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('buku.store') }}">
        @csrf
        <p>
            <label>Kode Buku (10 karakter)<br>
            <input type="text" name="kode_buku" value="{{ old('kode_buku') }}"></label>
        </p>
        <p>
            <label>Judul<br>
            <input type="text" name="judul" value="{{ old('judul') }}"></label>
        </p>
        <p>
            <label>ISBN (13 digit)<br>
            <input type="text" name="isbn" value="{{ old('isbn') }}"></label>
        </p>
        <p>
            <label>Tahun Terbit<br>
            <input type="number" name="tahun_terbit" value="{{ old('tahun_terbit') }}"></label>
        </p>
        <p>
            <label>Kategori<br>
            <select name="kategori_buku_id">
                <option value="">-- Pilih --</option>
                @foreach ($kategoriList as $k)
                    <option value="{{ $k->id }}" @if ((string) old('kategori_buku_id') === (string) $k->id) selected @endif>
                        {{ $k->nama_kategori }}
                    </option>
                @endforeach
            </select></label>
        </p>
        <button type="submit">Simpan</button>
    </form>

    <h2>Cari dan Filter</h2>
    <form method="GET" action="{{ route('buku.index') }}">
        <input type="text" name="q" value="{{ $q }}" placeholder="Kode, judul, atau ISBN">
        <select name="kategori_buku_id">
            <option value="">Semua Kategori</option>
            @foreach ($kategoriList as $k)
                <option value="{{ $k->id }}" @if ($kat === (int) $k->id) selected @endif>{{ $k->nama_kategori }}</option>
            @endforeach
        </select>
        <button type="submit">Terapkan</button>
        <a href="{{ route('buku.index') }}">Reset</a>
    </form>

    <h2>Daftar Buku</h2>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode</th>
                <th>Judul</th>
                <th>ISBN</th>
                <th>Tahun</th>
                <th>Status</th>
                <th>Kategori</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($bukuList as $i => $b)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $b->kode_buku }}</td>
                    <td>{{ $b->judul }}</td>
                    <td>{{ $b->isbn }}</td>
                    <td>{{ $b->tahun_terbit }}</td>
                    <td>
                        @if (statusTahunBuku((int) $b->tahun_terbit) === 'Baru')
                            Baru
                        @else
                            Lama
                        @endif
                    </td>
                    <td>{{ $b->nama_kategori }}</td>
                    <td>
                        <a href="{{ route('buku.edit', $b->id) }}">Edit</a>
                        <form method="POST" action="{{ route('buku.destroy', $b->id) }}" style="display:inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">Data tidak ditemukan.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>