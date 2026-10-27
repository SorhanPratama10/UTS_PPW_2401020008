<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Buku</title>
</head>
<body>
    <h1>Edit Buku</h1>

    @if (session('error'))
        <p><strong>Gagal:</strong> {{ session('error') }}</p>
    @endif
    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('buku.update', $buku->id) }}">
        @csrf
        @method('PUT')
        <p>
            <label>Kode Buku<br>
            <input type="text" name="kode_buku" value="{{ old('kode_buku', $buku->kode_buku) }}"></label>
        </p>
        <p>
            <label>Judul<br>
            <input type="text" name="judul" value="{{ old('judul', $buku->judul) }}"></label>
        </p>
        <p>
            <label>ISBN<br>
            <input type="text" name="isbn" value="{{ old('isbn', $buku->isbn) }}"></label>
        </p>
        <p>
            <label>Tahun Terbit<br>
            <input type="number" name="tahun_terbit" value="{{ old('tahun_terbit', $buku->tahun_terbit) }}"></label>
        </p>
        <p>
            <label>Kategori<br>
            <select name="kategori_buku_id">
                @foreach ($kategoriList as $k)
                    <option value="{{ $k->id }}" @if ((string) old('kategori_buku_id', $buku->kategori_buku_id) === (string) $k->id) selected @endif>
                        {{ $k->nama_kategori }}
                    </option>
                @endforeach
            </select></label>
        </p>
        <button type="submit">Simpan Perubahan</button>
        <a href="{{ route('buku.index') }}">Batal</a>
    </form>
</body>
</html>