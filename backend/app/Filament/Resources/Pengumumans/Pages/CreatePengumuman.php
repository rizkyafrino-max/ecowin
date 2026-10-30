<?php

namespace App\Filament\Resources\Pengumumans\Pages;

use App\Filament\Resources\Pengumumans\PengumumanResource;
use App\Models\Pengumuman;
use App\Services\PengumumanService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

class CreatePengumuman extends CreateRecord
{
    protected static string $resource = PengumumanResource::class;

    protected static bool $canCreateAnother = false;

    protected function getCreateFormAction(): Action
    {
        // Pengumuman tidak bisa ditarik setelah terkirim. Tombol kirim biasa (submit) melewati modal konfirmasi, jadi dipakai
        // aksi biasa yang memvalidasi dulu, menampilkan konfirmasi dengan jumlah penerima, baru memanggil create().
        return Action::make('create')
            ->label('Kirim pengumuman')
            ->keyBindings(['mod+s'])
            ->mountUsing(fn () => $this->form->getState()) // kolom wajib kosong/tidak valid: tampilkan galat, jangan buka konfirmasi
            ->requiresConfirmation()
            ->modalHeading('Kirim pengumuman?')
            ->modalDescription(function (): string {
                $user = auth()->user();
                $bankId = $user->isAdmin() ? ($this->data['bank_sampah_id'] ?? null) : $user->bank_sampah_id;
                $n = app(PengumumanService::class)->hitungPenerima($bankId ? (int) $bankId : null);
                $jadwal = ! empty($this->data['kirim_pada']) ? ' pada '.\Illuminate\Support\Carbon::parse($this->data['kirim_pada'])->format('d M Y H:i') : '';

                return $jadwal !== ''
                    ? "Pengumuman akan dijadwalkan{$jadwal} untuk {$n} nasabah aktif dan hanya bisa dibatalkan sebelum waktunya."
                    : "Pengumuman akan langsung diterima {$n} nasabah aktif dan tidak bisa ditarik setelah terkirim.";
            })
            ->modalSubmitActionLabel('Ya, kirim')
            ->action(fn () => $this->create());
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user = auth()->user();
        $layanan = app(PengumumanService::class);

        if ($layanan->melebihiBatas($user)) {
            Notification::make()->danger()->title('Batas harian tercapai')->body('Maksimal '.PengumumanService::BATAS_HARIAN.' pengumuman per 24 jam. Coba lagi nanti.')->send();
            throw new Halt;
        }

        $p = new Pengumuman;
        $p->forceFill([
            'dibuat_oleh' => $user->id,
            // Petugas selalu dikunci ke bank sampahnya sendiri, apa pun isi formulir.
            'bank_sampah_id' => $user->isAdmin() ? ($data['bank_sampah_id'] ?? null) : $user->bank_sampah_id,
            'judul' => trim(strip_tags((string) $data['judul'])),
            'isi' => trim(strip_tags((string) $data['isi'])),
            'kirim_pada' => ! empty($data['kirim_pada']) ? $data['kirim_pada'] : now(),
        ])->save();

        if ($p->kirim_pada->lte(now())) {
            $layanan->kirim($p->id);
            $p->refresh();
        }

        return $p;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return $this->record->sudahTerkirim()
            ? "Pengumuman terkirim ke {$this->record->jumlah_penerima} nasabah"
            : 'Pengumuman dijadwalkan';
    }
}
