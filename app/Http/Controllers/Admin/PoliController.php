<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poli;
use Illuminate\Http\Request;

class PoliController extends Controller
{
    public function index()
    {
        $polis = Poli::all();
        // Ubah 'poli' menjadi 'polis' agar sesuai nama folder Anda
        return view('admin.polis.index', compact('polis'));
    }

    public function create()
    {
        // Ubah 'poli' menjadi 'polis'
        return view('admin.polis.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_poli' => 'required|string|max:255',
            'keterangan' => 'required|string',
        ]);

        Poli::create($request->all());

        // Ubah redirect ke 'polis.index' (sesuai penamaan Route::resource)
        return redirect()->route('polis.index')->with('success', 'Poli berhasil ditambahkan');
    }

    public function edit(Poli $poli)
    {
        // Ubah 'poli' menjadi 'polis'
        return view('admin.polis.edit', compact('poli'));
    }

    public function update(Request $request, Poli $poli)
    {
        $request->validate([
            'nama_poli' => 'required|string|max:255',
            'keterangan' => 'required|string',
        ]);

        $poli->update($request->all());

        // Ubah redirect ke 'polis.index'
        return redirect()->route('polis.index')->with('success', 'Poli berhasil diperbarui');
    }

    public function destroy(Poli $poli)
    {
        $poli->delete();

        // Ubah redirect ke 'polis.index'
        return redirect()->route('polis.index')->with('success', 'Poli berhasil dihapus');
    }
}