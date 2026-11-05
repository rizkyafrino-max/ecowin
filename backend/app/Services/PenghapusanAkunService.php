<?php

namespace App\Services;

use App\Models\AktivitasBiopori;
use App\Models\Nasabah;
use App\Models\PenarikanSaldo;
use App\Models\TitikBiopori;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Penghapusan akun yang sudah tidak aktif (opsional, hanya Admin).
 *
 * Riwayat keuangan dan audit log wajib tetap utuh, sehingga yang dihapus adalah DATA PRIBADI
 * (nama, email, HP, alamat, NIK, foto identitas, ID Google, token) dan barisnya disembunyikan
 * (soft delete). Transaksi lama tetap menunjuk ke baris itu dan tampil sebagai "Nasabah dihapus".
 */
class PenghapusanAkunService
{
    public function __construct(private AuditLogger $audit) {}

    public function hapusNasabah(User $actor, Nasabah $nasabah): void
    {
        $this->pastikanAdmin($actor);

        $user = $nasabah->user()->withTrashed()->first();
        if ($nasabah->status === 'aktif' || ($user && $user->isActive())) {
            $this->tolak('Hanya nasabah yang sudah tidak aktif yang bisa dihapus. Nonaktifkan dulu akunnya.');
        }
        $this->pastikanTanpaTanggungan($nasabah, sendiri: false);

        $this->hapusDataNasabah($actor, $nasabah, $user, 'hapus_nasabah');
    }

    /**
     * Penghapusan oleh nasabah sendiri dari aplikasi (wajib untuk Google Play). Aturan tanggungan sama dengan
     * penghapusan oleh Admin; bedanya akun boleh masih aktif karena pemiliknya sendiri yang memintanya.
     */
    public function hapusAkunSendiri(User $user): void
    {
        if (! $user->isNasabah() || ! $user->isActive()) {
            throw new AuthorizationException('Hanya akun nasabah yang aktif yang dapat dihapus sendiri.');
        }

        $nasabah = $user->nasabah;
        if (! $nasabah) {
            $this->tolak('Data nasabah tidak ditemukan.');
        }
        $this->pastikanTanpaTanggungan($nasabah, sendiri: true);

        $this->hapusDataNasabah($user, $nasabah, $user, 'hapus_akun_sendiri');
    }

    /**
     * Saldo harus Rp 0 dan tidak ada permintaan yang menunggu keputusan. Pesan untuk nasabah yang menghapus akunnya sendiri
     * memakai bahasa sehari-hari; pesan untuk Admin tetap baku.
     */
    private function pastikanTanpaTanggungan(Nasabah $nasabah, bool $sendiri): void
    {
        if ((float) $nasabah->saldo > 0) {
            $nominal = number_format((float) $nasabah->saldo, 0, ',', '.');
            $this->tolak($sendiri
                ? "Saldomu masih Rp {$nominal}. Ajukan penarikan sampai saldo Rp 0, lalu hapus akun."
                : "Saldo nasabah masih Rp {$nominal}. Selesaikan penarikan sampai saldo Rp 0 sebelum menghapus.");
        }
        if (PenarikanSaldo::query()->where('nasabah_id', $nasabah->id)->where('status', PenarikanSaldo::STATUS_PENDING)->exists()) {
            $this->tolak($sendiri
                ? 'Masih ada penarikan saldo yang menunggu keputusan petugas. Tunggu selesai dulu ya, baru hapus akun.'
                : 'Masih ada penarikan saldo yang menunggu keputusan. Selesaikan atau tolak dulu.');
        }
        if (AktivitasBiopori::query()->where('nasabah_id', $nasabah->id)->where('status', AktivitasBiopori::STATUS_PENDING)->exists()) {
            $this->tolak($sendiri
                ? 'Masih ada aktivitas organik yang menunggu verifikasi petugas. Tunggu selesai diperiksa dulu ya.'
                : 'Masih ada aktivitas organik yang menunggu verifikasi. Tunggu hingga selesai diperiksa.');
        }
    }

    private function hapusDataNasabah(User $actor, Nasabah $nasabah, ?User $user, string $aksiAudit): void
    {
        DB::transaction(function () use ($actor, $nasabah, $user, $aksiAudit): void {
            $jejak = ['nasabah_id' => $nasabah->id, 'nomor_nasabah' => $nasabah->nomor_nasabah, 'bank_sampah_id' => $nasabah->bank_sampah_id];

            foreach ([config('ecowin.upload.disk'), 'public'] as $disk) {
                if ($nasabah->foto_ktp_kk_path && $disk) {
                    Storage::disk($disk)->delete($nasabah->foto_ktp_kk_path);
                }
            }

            $nasabah->forceFill([
                'nama' => 'Nasabah dihapus',
                'no_hp' => 'terhapus-'.$nasabah->id.'-'.Str::lower(Str::random(6)),
                'alamat_rt_rw' => '-',
                'nisn_atau_nik' => null,
                'foto_ktp_kk_path' => null,
                'kartu_qr_token' => Str::random(48), // kartu lama tidak berlaku lagi
                'status' => 'nonaktif',
            ])->save();

            if ($user) {
                $this->hapusPribadiUser($user, 'Pengguna dihapus');
                $user->delete();
            }

            $nasabah->delete();
            $this->audit->log($aksiAudit, null, $jejak, null, $actor);
        });
    }

    public function hapusPetugas(User $actor, User $petugas): void
    {
        $this->pastikanAdmin($actor);

        if ($petugas->is($actor)) {
            $this->tolak('Anda tidak bisa menghapus akun Anda sendiri.');
        }
        if (! $petugas->isPetugas()) {
            $this->tolak('Hanya akun petugas yang bisa dihapus dari sini.');
        }
        if ($petugas->isActive()) {
            $this->tolak('Hanya petugas yang sudah tidak aktif yang bisa dihapus. Nonaktifkan dulu akunnya.');
        }

        DB::transaction(function () use ($actor, $petugas): void {
            $jejak = ['user_id' => $petugas->id, 'role' => 'petugas', 'bank_sampah_id' => $petugas->bank_sampah_id];

            $this->hapusPribadiUser($petugas, 'Petugas dihapus');
            $petugas->delete();
            $this->audit->log('hapus_petugas', null, $jejak, null, $actor);
        });
    }

    /** Menghapus data pribadi akun, mencabut seluruh sesi dan token. */
    private function hapusPribadiUser(User $user, string $nama): void
    {
        $user->tokens()->delete();
        \App\Models\PerangkatPush::query()->where('user_id', $user->id)->delete();
        DB::table('sessions')->where('user_id', $user->id)->delete();
        app(\App\Services\FotoProfilService::class)->hapus($user); // buang berkas foto profil

        $user->forceFill([
            'nama' => $nama,
            'email' => 'terhapus-'.$user->id.'-'.Str::lower(Str::random(8)).'@ecowin.invalid',
            'google_id' => null,
            'avatar' => null,
            'jenis_kelamin' => null,
            'foto_profil' => null,
            'remember_token' => null,
            'status' => User::STATUS_NONAKTIF,
        ])->save();

        TitikBiopori::query()->where('diajukan_oleh', $user->id)->where('verifikasi', TitikBiopori::VERIFIKASI_PENDING)
            ->update(['verifikasi' => TitikBiopori::VERIFIKASI_REJECTED, 'catatan_verifikasi' => 'Akun pengaju dihapus.']);
    }

    private function pastikanAdmin(User $actor): void
    {
        if (! $actor->isAdmin() || ! $actor->isActive()) {
            throw new AuthorizationException('Hanya Admin yang dapat menghapus data akun.');
        }
    }

    private function tolak(string $pesan): never
    {
        throw ValidationException::withMessages(['akun' => [$pesan]]);
    }
}
