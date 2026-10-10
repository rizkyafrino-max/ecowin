<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\BankSampah;
use App\Models\Nasabah;
use App\Models\User;
use App\Services\Auth\GoogleIdentity;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Pendaftaran nasabah oleh Admin/Petugas. Nasabah login memakai email Google
 * yang didaftarkan di sini (tidak ada PIN/password).
 */
class NasabahService
{
    /**
     * @param  array{nama: string, email: string, no_hp: string, alamat_rt_rw: string, nisn_atau_nik?: string|null, bank_sampah_id?: int|null}  $data
     */
    public function daftarkan(User $actor, array $data, ?string $fotoKtpPath = null): Nasabah
    {
        if (! $actor->isStaff()) {
            throw new AuthorizationException('Anda tidak berhak mendaftarkan nasabah.');
        }

        // Petugas selalu memakai bank sampah miliknya; input bank_sampah_id diabaikan.
        $bankId = $actor->isAdmin() ? ($data['bank_sampah_id'] ?? null) : $actor->bank_sampah_id;

        if (! $bankId || ! BankSampah::query()->whereKey($bankId)->exists()) {
            throw ValidationException::withMessages(['bank_sampah_id' => ['Bank sampah wajib dipilih.']]);
        }

        $email = mb_strtolower(trim($data['email']));

        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => ['Email sudah terdaftar.']]);
        }

        return DB::transaction(function () use ($actor, $data, $bankId, $email, $fotoKtpPath): Nasabah {
            $user = new User;
            $user->forceFill([
                'nama' => $data['nama'],
                'email' => $email,
                'role' => Role::Nasabah->value,
                'status' => User::STATUS_AKTIF,
                'bank_sampah_id' => $bankId,
            ])->save();

            $nasabah = new Nasabah;
            $nasabah->fill([
                'nama' => $data['nama'],
                'no_hp' => $data['no_hp'],
                'alamat_rt_rw' => $data['alamat_rt_rw'],
                'nisn_atau_nik' => $data['nisn_atau_nik'] ?? null,
            ]);
            $nasabah->forceFill([
                'user_id' => $user->id,
                'bank_sampah_id' => $bankId,
                'foto_ktp_kk_path' => $fotoKtpPath,
                'kartu_qr_token' => Str::random(48),
                'status_verifikasi' => 'verified',
                'status' => 'aktif',
                'saldo' => 0,
                'dibuat_oleh' => $actor->id,
            ])->save();

            $nasabah->forceFill(['nomor_nasabah' => self::nomorNasabah($nasabah)])->save();

            return $nasabah;
        });
    }

    /**
     * Pendaftaran mandiri lewat Google. Identitas (email/Google ID/nama) berasal dari token
     * Google yang sudah diverifikasi server, BUKAN dari form. Role selalu nasabah dan status
     * verifikasi awal `pending` sampai Petugas/Admin menyetujui.
     *
     * @param  array{nama: string, no_hp: string, alamat_rt_rw: string, bank_sampah_id: int}  $data
     */
    public function daftarMandiri(GoogleIdentity $identity, array $data): Nasabah
    {
        if (! $identity->emailVerified) {
            throw ValidationException::withMessages(['email' => ['Email Google belum terverifikasi.']]);
        }

        $bank = BankSampah::query()->whereKey($data['bank_sampah_id'])->where('status', 'aktif')->first();

        if (! $bank) {
            throw ValidationException::withMessages(['bank_sampah_id' => ['Bank Sampah tidak tersedia.']]);
        }

        if (User::query()->where('email', $identity->email)->orWhere('google_id', $identity->googleId)->exists()) {
            throw ValidationException::withMessages(['email' => ['Akun Google ini sudah terdaftar. Silakan masuk.']]);
        }

        return DB::transaction(function () use ($identity, $data, $bank): Nasabah {
            $user = new User;
            $user->forceFill([
                'nama' => $data['nama'],
                'email' => $identity->email,
                'google_id' => $identity->googleId,
                'email_verified_at' => now(),
                'avatar' => $identity->avatar,
                'role' => Role::Nasabah->value,
                'status' => User::STATUS_AKTIF,
                'bank_sampah_id' => $bank->id,
            ])->save();

            $nasabah = new Nasabah;
            $nasabah->fill([
                'nama' => $data['nama'],
                'no_hp' => $data['no_hp'],
                'alamat_rt_rw' => $data['alamat_rt_rw'],
            ]);
            $nasabah->forceFill([
                'user_id' => $user->id,
                'bank_sampah_id' => $bank->id,
                'kartu_qr_token' => Str::random(48),
                'status_verifikasi' => 'pending',
                'status' => 'aktif',
                'saldo' => 0,
                'dibuat_oleh' => null,
            ])->save();

            $nasabah->forceFill(['nomor_nasabah' => self::nomorNasabah($nasabah)])->save();

            return $nasabah;
        });
    }

    /** Petugas/Admin menyetujui pendaftaran mandiri: Nasabah boleh menarik saldo. Tercatat di audit log. */
    public function verifikasi(User $actor, Nasabah $nasabah): Nasabah
    {
        if (! $actor->isStaff() || ! $actor->canManageBankSampah($nasabah->bank_sampah_id)) {
            throw new AuthorizationException('Anda tidak berhak memverifikasi Nasabah ini.');
        }

        if ($nasabah->status_verifikasi === 'verified') {
            return $nasabah;
        }

        if ($nasabah->status !== 'aktif' || ! ($nasabah->user?->isActive() ?? true)) {
            throw ValidationException::withMessages(['status' => ['Akun nonaktif tidak dapat diverifikasi. Aktifkan akun lebih dulu.']]);
        }

        $before = ['status_verifikasi' => $nasabah->status_verifikasi];
        $nasabah->forceFill(['status_verifikasi' => 'verified'])->save();
        app(AuditLogger::class)->log('verifikasi_nasabah', $nasabah, $before, ['status_verifikasi' => 'verified'], $actor);

        return $nasabah;
    }

    /** Petugas/Admin menolak pendaftaran mandiri: akun dinonaktifkan (tidak bisa masuk), alasan tercatat di audit log. */
    public function tolak(User $actor, Nasabah $nasabah, string $alasan): Nasabah
    {
        if (! $actor->isStaff() || ! $actor->canManageBankSampah($nasabah->bank_sampah_id)) {
            throw new AuthorizationException('Anda tidak berhak menolak Nasabah ini.');
        }

        if ($nasabah->status_verifikasi !== 'pending') {
            throw ValidationException::withMessages(['status' => ['Hanya pendaftaran yang masih menunggu yang dapat ditolak.']]);
        }

        $before = ['status' => $nasabah->status, 'status_verifikasi' => $nasabah->status_verifikasi];

        DB::transaction(function () use ($nasabah): void {
            $nasabah->forceFill(['status' => 'nonaktif'])->save();
            $nasabah->user?->forceFill(['status' => User::STATUS_NONAKTIF])->save();
            $nasabah->user?->tokens()->delete();
        });

        app(AuditLogger::class)->log('tolak_nasabah', $nasabah, $before, ['status' => 'nonaktif', 'alasan' => $alasan], $actor);

        return $nasabah;
    }

    public static function nomorNasabah(Nasabah $nasabah): string
    {
        return 'NSB-'.str_pad((string) $nasabah->id, 6, '0', STR_PAD_LEFT);
    }
}
