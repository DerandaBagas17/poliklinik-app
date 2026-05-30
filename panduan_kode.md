# Panduan Implementasi Manual: Stok Obat

Ikuti langkah-langkah di bawah ini untuk menambahkan fitur stok obat ke dalam proyek Anda. Salin dan tempel (copy-paste) kode yang diberikan ke file yang sesuai.

---

### 1. Buat dan Jalankan Migrasi Database
Buka terminal/CMD di folder proyek Anda, lalu jalankan perintah ini:
```bash
php artisan make:migration add_stok_to_obat_table --table=obat
```

Buka file migrasi yang baru saja dibuat di dalam folder `database/migrations/` dan ubah isinya menjadi:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obat', function (Blueprint $table) {
            $table->integer('stok')->default(0)->after('harga');
        });
    }

    public function down(): void
    {
        Schema::table('obat', function (Blueprint $table) {
            $table->dropColumn('stok');
        });
    }
};
```
Simpan file, lalu jalankan migrasi di terminal:
```bash
php artisan migrate
```

---

### 2. Update Model Obat
Buka app/Models/Obat.php. Tambahkan `'stok'` ke dalam `$fillable`.

```php
    protected $fillable = [
        'nama_obat',
        'kemasan',
        'harga',
        'stok', // Tambahkan baris ini
    ];
```

---

### 3. Update Controller Admin
Buka app/Http/Controllers/Admin/ObatController.php.

Pada method **`store`**, ubah bagian validasi dan create:
```php
    public function store(Request $request)
    {
        $request->validate([
            'nama_obat' => 'required|string',
            'kemasan' => 'required|string',
            'harga' => 'required|integer',
            'stok' => 'required|integer|min:0', // Validasi stok
        ]);

        Obat::create([
            'nama_obat' => $request->nama_obat,
            'kemasan' => $request->kemasan,
            'harga' => $request->harga,
            'stok' => $request->stok // Simpan stok
        ]);

        return redirect()->route('obat.index')
            ->with('message', 'Data Obat Berhasil dibuat')
            ->with('type', 'success');
    }
```

Pada method **`update`**, ubah bagian validasi dan update:
```php
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama_obat' => 'required|string',
            'kemasan' => 'nullable|string',
            'harga' => 'required|integer',
            'stok' => 'required|integer|min:0', // Validasi stok
        ]);

        $obat = Obat::findOrFail($id);
        $obat->update([
            'nama_obat' => $request->nama_obat,
            'kemasan' => $request->kemasan,
            'harga' => $request->harga,
            'stok' => $request->stok // Update stok
        ]);

        return redirect()->route('obat.index')
            ->with('message', 'Data Obat berhasil di edit')
            ->with('type', 'success');
    }
```

---

### 4. Update View Admin (Tambah & Edit)

Buka resources/views/admin/obat/create.blade.php.
Tambahkan kode form input ini **di bawah bagian input Harga**, tepat sebelum tombol *Simpan*:
```blade
                {{-- Stok --}}
                <div class="mb-8">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">
                        Stok Awal <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="stok" value="{{ old('stok', 0) }}" placeholder="0" min="0" step="1"
                        class="w-full px-4 py-2 border-2 rounded-lg p-2
                                  focus:border-primary focus:outline-none
                                  @error('stok') border-red-500 @enderror" required>
                    @error('stok')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
```

Buka resources/views/admin/obat/edit.blade.php.
Tambahkan form input stok ini **di bawah bagian input Harga**, tepat sebelum tombol *Update*:
```blade
                {{-- Stok --}}
                <div class="mb-8">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">
                        Stok <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="stok" value="{{ old('stok', $obat->stok) }}" placeholder="0" min="0" step="1"
                        class="w-full px-4 py-2 border-2 rounded-lg p-2
                                  focus:border-primary focus:outline-none
                                  @error('stok') border-red-500 @enderror" required>
                    @error('stok')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
```

---

### 5. Update View Admin (Tabel Index)

Buka resources/views/admin/obat/index.blade.php.
Pada bagian `<thead >...</thead>`, tambahkan judul kolom Stok sebelum kolom Aksi:
```blade
<th class="px-6 py-4">Harga</th>
<th class="px-6 py-4">Stok</th> <!-- TAMBAHKAN INI -->
<th class="px-6 py-4 text-right">Aksi</th>
```

Pada bagian `<tbody>...</tbody>`, tambahkan data kolom Stok dan indikator sebelum `<td>` aksi:
```blade
<td class="px-6 py-4 font-semibold text-slate-800">
    Rp {{ number_format($obat->harga, 0, ',', '.') }}
</td>

<!-- TAMBAHKAN BLOK TD INI -->
<td class="px-6 py-4 font-semibold text-slate-800">
    {{ $obat->stok }}
    @if($obat->stok <= 2 && $obat->stok > 0)
        <span class="text-xs text-orange-500 ml-1">(Menipis)</span>
    @elseif($obat->stok == 0)
        <span class="text-xs text-red-500 ml-1">(Habis)</span>
    @endif
</td>
<!-- SAMPAI SINI -->

<td class="px-6 py-4 text-right">
```

---

### 6. Update Controller Dokter (Pengurangan Stok & Validasi)

Buka app/Http/Controllers/Dokter/PeriksaPasienController.php.
Ganti **keseluruhan method `store`** menjadi seperti ini:

```php
    public function store(Request $request)
    {
        $request->validate([
            'obat_json' => 'required',
            'catatan' => 'nullable|string',
            'biaya_periksa' => 'required|integer',
        ]);

        $obatIds = json_decode($request->obat_json, true);

        // 1. Validasi Stok Obat sebelum menyimpan data apa pun
        if ($obatIds && count($obatIds) > 0) {
            foreach ($obatIds as $idObat) {
                $obat = Obat::find($idObat);
                if (!$obat || $obat->stok < 1) {
                    return redirect()->back()->with('error', 'Gagal menyimpan: Stok obat ' . ($obat ? $obat->nama_obat : '') . ' sedang kosong/habis.');
                }
            }
        }

        // 2. Buat data Periksa
        $periksa = Periksa::create([
            'id_daftar_poli' => $request->id_daftar_poli,
            'tgl_periksa' => now(),
            'catatan' => $request->catatan,
            'biaya_periksa' => $request->biaya_periksa + 150000,
        ]);

        // 3. Simpan Detail Periksa sekaligus kurangi stok
        if ($obatIds && count($obatIds) > 0) {
            foreach ($obatIds as $idObat) {
                DetailPeriksa::create([
                    'id_periksa' => $periksa->id,
                    'id_obat' => $idObat,
                ]);

                // Kurangi Stok Obat
                $obat = Obat::find($idObat);
                $obat->stok -= 1;
                $obat->save();
            }
        }

        return redirect()->route('periksa-pasien.index')->with('success', 'Data periksa berhasil disimpan.');
    }
```

---

### 7. Update View Dokter (Error Alert & Indikator Stok)

Buka resources/views/dokter/periksa-pasien/create.blade.php.

Tambahkan kode alert ini di bawah baris `<div class="card-body p-8">` (kira-kira sebelum `<form ...>`):
```blade
            @if(session('error'))
            <div class="px-4 py-3 mb-6 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
            </div>
            @endif
```

Ubah bagian `<select id="select-obat" ...>` loop `@foreach ($obats as $obat)` menjadi seperti ini:
```blade
                    <select id="select-obat" class="select select-bordered w-full rounded-lg border-2 px-4">
                        <option value="">-- Pilih Obat --</option>
                        @foreach ($obats as $obat)
                            <option value="{{ $obat->id }}"
                                data-nama="{{ $obat->nama_obat }}"
                                data-harga="{{ $obat->harga }}"
                                {{ $obat->stok == 0 ? 'disabled' : '' }}>
                                
                                {{ $obat->nama_obat }} - Rp{{ number_format($obat->harga) }} 
                                (Stok: {{ $obat->stok }})
                                
                                {{ $obat->stok <= 2 && $obat->stok > 0 ? '[Menipis]' : '' }}
                                {{ $obat->stok == 0 ? '[Habis]' : '' }}
                            </option>
                        @endforeach
                    </select>
```

Selesai! Setelah memasukkan semua kode tersebut, pastikan untuk menjalankan `php artisan migrate` di terminal untuk menambahkan field ke database sebelum Anda menguji aplikasinya.
