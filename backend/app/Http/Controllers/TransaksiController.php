<?php

namespace App\Http\Controllers;

use App\Models\HargaSampah;
use App\Models\Nasabah;
use App\Models\TransaksiAnorganik;
use App\Models\TransaksiOrganik;
use App\Models\User;
use Illuminate\Http\Request;

class TransaksiController extends Controller
{
    private function actor(Request $request): object
    {
        return $request->user();
    }

    public function storeAnorganik(Request $request)
    {
        abort_unless($request->user() instanceof User, 403);
        $validated = $request->validate([
            'nasabah_id' => ['required', 'exists:nasabah,id'],
            'harga_sampah_id' => ['required', 'exists:harga_sampah,id'],
            'berat_kg' => ['required', 'numeric', 'gt:0'],
            'foto_dokumentasi' => ['nullable', 'image', 'max:2048'],
        ]);
        $actor = $this->actor($request);
        $nasabah = Nasabah::findOrFail($validated['nasabah_id']);
        abort_if($actor instanceof Nasabah ? $nasabah->id !== $actor->id : ($actor->isPetugas() && $nasabah->bank_sampah_id !== $actor->bank_sampah_id), 403);
        $harga = HargaSampah::findOrFail($validated['harga_sampah_id']);
        $nilai = (int) round((float) $validated['berat_kg'] * $harga->harga_per_kg);
        $data = [
            'nasabah_id' => $nasabah->id,
            'bank_sampah_id' => $nasabah->bank_sampah_id,
            'harga_sampah_id' => $harga->id,
            'berat_kg' => $validated['berat_kg'],
            'nilai_rupiah' => $nilai,
            'dicatat_oleh' => $actor->id,
        ];
        if ($request->hasFile('foto_dokumentasi')) {
            $data['foto_dokumentasi_path'] = $request->file('foto_dokumentasi')->store('dokumentasi-anorganik', 'public');
        }

        return response()->json(TransaksiAnorganik::create($data), 201);
    }

    public function storeOrganik(Request $request)
    {
        abort_unless($request->user() instanceof User, 403);
        $validated = $request->validate([
            'nasabah_id' => ['required', 'exists:nasabah,id'],
            'jenis_organik' => ['required', 'string', 'max:100'],
            'berat_kg' => ['required', 'numeric', 'gt:0'],
            'checklist_bebas_plastik' => ['required', 'boolean'],
            'checklist_bebas_logam' => ['required', 'boolean'],
        ]);
        abort_unless($validated['checklist_bebas_plastik'] && $validated['checklist_bebas_logam'], 422, 'Sampah organik wajib bebas plastik dan logam.');
        $actor = $this->actor($request);
        $nasabah = Nasabah::findOrFail($validated['nasabah_id']);
        abort_if($actor instanceof Nasabah ? $nasabah->id !== $actor->id : ($actor->isPetugas() && $nasabah->bank_sampah_id !== $actor->bank_sampah_id), 403);

        return response()->json(TransaksiOrganik::create([
            'nasabah_id' => $nasabah->id,
            'bank_sampah_id' => $nasabah->bank_sampah_id,
            'jenis_organik' => $validated['jenis_organik'],
            'berat_kg' => $validated['berat_kg'],
            'checklist_bebas_plastik' => true,
            'checklist_bebas_logam' => true,
            'estimasi_kompos_kg' => $validated['berat_kg'] * 0.5,
            'dicatat_oleh' => $actor->id,
        ]), 201);
    }

    public function index(Request $request)
    {
        $actor = $request->user();
        $nasabahId = $actor instanceof Nasabah ? $actor->id : $request->integer('nasabah_id');
        $apply = function ($query) use ($actor, $nasabahId, $request) {
            if ($nasabahId) {
                $query->where('nasabah_id', $nasabahId);
            }
            if ($actor instanceof User && $actor->isPetugas()) {
                $query->where('bank_sampah_id', $actor->bank_sampah_id);
            }

            return $query->when($request->filled('dari'), fn ($q) => $q->whereDate('created_at', '>=', $request->dari))->when($request->filled('sampai'), fn ($q) => $q->whereDate('created_at', '<=', $request->sampai))->latest()->get();
        };
        $tipe = $request->get('tipe', 'semua');

        return response()->json(array_filter([
            'anorganik' => in_array($tipe, ['semua', 'anorganik'], true) ? $apply(TransaksiAnorganik::query()) : null,
            'organik' => in_array($tipe, ['semua', 'organik'], true) ? $apply(TransaksiOrganik::query()) : null,
        ], fn ($value) => $value !== null));
    }
}
