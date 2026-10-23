<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Services\AuditLogger;
use App\Services\FotoProfilService;
use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use SensitiveParameter;

/**
 * Profil: nama, jenis kelamin, dan foto profil. Email = akun Google (dikelola Admin), tidak ada password karena
 * login hanya lewat Google. Foto diproses FotoProfilService (diperiksa dari isi berkas, digambar ulang tanpa metadata).
 */
class EditUserProfile extends EditProfile
{
    protected static ?string $title = 'Profil saya';

    public static function getLabel(): string
    {
        return 'Profil saya';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.profil-avatar'),
            FileUpload::make('foto')->label('Ganti foto profil')->image()->avatar()->storeFiles(false)
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize((int) (FotoProfilService::MAKS_BYTE / 1024))
                ->helperText('JPEG, PNG, atau WebP, maksimal 3 MB. Foto dipotong menjadi persegi.'),
            Toggle::make('hapus_foto')->label('Hapus foto saya dan pakai avatar bawaan')
                ->visible(fn (): bool => $this->getUser()->foto_profil !== null),
            $this->getNameFormComponent(),
            Select::make('jenis_kelamin')->label('Jenis kelamin')->options(User::JENIS_KELAMIN)->native(false)
                ->placeholder('Pilih…')->helperText('Menentukan avatar bawaan bila Anda belum memasang foto sendiri.'),
            TextInput::make('email')->label('Email Google')->disabled()->dehydrated(false),
        ]);
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('nama')->label('Nama lengkap')->required()->maxLength(150)->autofocus();
    }

    protected function handleRecordUpdate(Model $record, #[SensitiveParameter] array $data): Model
    {
        /** @var User $record */
        $foto = $data['foto'] ?? null;
        if (is_array($foto)) {
            $foto = Arr::first($foto);
        }
        $hapus = (bool) ($data['hapus_foto'] ?? false);
        $sebelum = $record->only(['nama', 'jenis_kelamin']);

        $record->forceFill([
            'nama' => $data['nama'],
            'jenis_kelamin' => in_array($data['jenis_kelamin'] ?? null, ['L', 'P'], true) ? $data['jenis_kelamin'] : null,
        ])->save();

        $layanan = app(FotoProfilService::class);
        if ($foto instanceof TemporaryUploadedFile) {
            try {
                $layanan->simpan($record, (string) file_get_contents($foto->getRealPath()));
            } catch (ValidationException $e) {
                Notification::make()->danger()->title('Foto tidak dipakai')->body(collect($e->errors())->flatten()->first())->send();
            }
            $foto->delete();
        } elseif ($hapus) {
            $layanan->hapus($record);
        }

        app(AuditLogger::class)->log('ubah_profil', $record, $sebelum, $record->only(['nama', 'jenis_kelamin']) + ['foto' => $record->foto_profil !== null], $record);

        return $record;
    }

    /** Setelah disimpan, kosongkan kolom unggahan dan toggle hapus agar tidak terkirim ulang. */
    protected function afterSave(): void
    {
        $this->data['foto'] = null;
        $this->data['hapus_foto'] = false;
    }

    protected function mutateFormDataBeforeSave(#[SensitiveParameter] array $data): array
    {
        return $data;
    }
}
