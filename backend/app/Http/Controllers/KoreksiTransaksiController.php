<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\KoreksiTransaksi;
use App\Models\TransaksiAnorganik;
use App\Models\TransaksiOrganik;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KoreksiTransaksiController extends Controller
{
    public function store(Request $request)
    {
        abort_unless($request->user() instanceof User, 403);
        $validated = $request->validate([
            'transaksi_id' => 'required|integer',
            'tipe_transaksi' => 'required|in:anorganik,organik',
            'alasan' => 'required|string',
        ]);

        $model = $validated['tipe_transaksi'] === 'anorganik' ? TransaksiAnorganik::class : TransaksiOrganik::class;
        $transaksi = $model::query()->findOrFail($validated['transaksi_id']);
        $actor = $request->user();
        abort_unless($actor->isAdmin() || ($actor->isPetugas() && $actor->bank_sampah_id === $transaksi->bank_sampah_id), 403);

        $koreksi = KoreksiTransaksi::create([
            'transaksi_id' => $validated['transaksi_id'],
            'tipe_transaksi' => $validated['tipe_transaksi'],
            'diajukan_oleh' => $request->user()->id,
            'status' => 'menunggu',
            'alasan' => $validated['alasan'],
        ]);

        AuditLog::create([
            'user_id' => $actor->id,
            'bank_sampah_id' => $transaksi->bank_sampah_id,
            'aksi' => 'ajukan_koreksi_transaksi',
            'detail' => json_encode(['koreksi_id' => $koreksi->id, 'transaksi_id' => $transaksi->id, 'tipe' => $validated['tipe_transaksi']]),
        ]);

        return response()->json($koreksi, 201);
    }

    public function setujui(Request $request, KoreksiTransaksi $koreksi)
    {
        $actor = $request->user();
        abort_unless($actor instanceof User && ($actor->isAdmin() || $actor->isPetugas()), 403);
        $validated = $request->validate(['status' => ['sometimes', 'in:disetujui,ditolak']]);
        $koreksi = DB::transaction(function () use ($actor, $koreksi, $validated): KoreksiTransaksi {
            $locked = KoreksiTransaksi::query()->lockForUpdate()->findOrFail($koreksi->id);
            abort_if($locked->status !== 'menunggu', 422, 'Koreksi ini sudah diproses.');
            abort_if($locked->diajukan_oleh === $actor->id, 422, 'Koreksi tidak bisa diproses oleh orang yang sama dengan pengaju.');

            $model = $locked->tipe_transaksi === 'anorganik' ? TransaksiAnorganik::class : TransaksiOrganik::class;
            $transaksi = $model::query()->findOrFail($locked->transaksi_id);
            abort_unless($actor->isAdmin() || $actor->bank_sampah_id === $transaksi->bank_sampah_id, 403);

            $locked->update([
                'status' => $validated['status'] ?? 'disetujui',
                'disetujui_oleh' => $actor->id,
            ]);

            AuditLog::create([
                'user_id' => $actor->id,
                'bank_sampah_id' => $transaksi->bank_sampah_id,
                'aksi' => 'koreksi_transaksi_'.$locked->status,
                'detail' => json_encode(['koreksi_id' => $locked->id, 'transaksi_id' => $transaksi->id]),
            ]);

            return $locked;
        });

        return response()->json($koreksi);
    }
}
