<?php

namespace App\Http\Controllers\Concerns;

use App\Models\BankSampah;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Membaca dan memvalidasi filter laporan (periode + Bank Sampah) dengan aturan akses yang sama untuk cetak dan ekspor:
 * Petugas selalu dikunci ke Bank Sampah miliknya; Admin boleh "semua" atau satu Bank Sampah yang valid.
 */
trait MenyaringLaporan
{
    /**
     * @return array{dari: ?Carbon, sampai: ?Carbon, bankId: ?int, bank: ?BankSampah}
     */
    protected function filterLaporan(Request $request): array
    {
        $user = $request->user();
        abort_unless($user && $user->isStaff() && $user->isActive(), 403);

        $data = $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'bank' => ['nullable', 'string', Rule::when(fn () => ! in_array($request->input('bank'), [null, '', 'semua'], true), ['regex:/^\d{1,10}$/'])],
        ]);

        $dari = ! empty($data['dari']) ? Carbon::parse($data['dari']) : null;
        $sampai = ! empty($data['sampai']) ? Carbon::parse($data['sampai']) : null;

        $bankId = null;
        if ($user->isPetugas()) {
            $bankId = (int) $user->bank_sampah_id ?: null;
            abort_if($bankId === null, 403);
        } elseif (! in_array($data['bank'] ?? null, [null, '', 'semua'], true)) {
            $bankId = (int) $data['bank'];
            abort_unless(BankSampah::query()->whereKey($bankId)->exists(), 422, 'Bank Sampah tidak ditemukan.');
        }

        return ['dari' => $dari, 'sampai' => $sampai, 'bankId' => $bankId, 'bank' => $bankId ? BankSampah::query()->find($bankId) : null];
    }
}
