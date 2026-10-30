<?php

namespace App\Filament\Forms;

use App\Support\Desimal;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Input angka desimal berformat Indonesia. Pengguna mengetik tanpa tanda koma: diawali 0 langsung menjadi
 * koma ("05" → "0,5"). Di database tetap titik. Validasi rentang dilakukan terhadap nilai terparse.
 */
class DecimalInput extends TextInput
{
    protected int $angkaDesimal = 2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type('text')
            ->inputMode('decimal')
            ->autocomplete(false)
            ->extraInputAttributes(fn (): array => ['data-eco-decimal' => (string) $this->angkaDesimal])
            ->formatStateUsing(fn (mixed $state): ?string => Desimal::tampil($state))
            ->dehydrateStateUsing(fn (mixed $state): ?string => Desimal::simpan($state))
            ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                if ($value === null || $value === '') {
                    return;
                }
                if (! Desimal::valid($value, $this->angkaDesimal)) {
                    $fail('Isi angka seperti 0,5 (maksimal '.$this->angkaDesimal.' angka di belakang koma).');
                }
            });
    }

    public function desimal(int $angka): static
    {
        $this->angkaDesimal = $angka;

        return $this;
    }

    public function batasMin(float $min, bool $sertaiBatas = true): static
    {
        return $this->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail) use ($min, $sertaiBatas): void {
            $n = Desimal::parse($value);
            if ($n !== null && ($sertaiBatas ? $n < $min : $n <= $min)) {
                $fail('Nilai harus '.($sertaiBatas ? 'minimal ' : 'lebih dari ').str_replace('.', ',', (string) $min).'.');
            }
        });
    }

    public function batasMax(float $max): static
    {
        return $this->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail) use ($max): void {
            $n = Desimal::parse($value);
            if ($n !== null && $n > $max) {
                $fail('Nilai maksimal '.str_replace('.', ',', (string) $max).'.');
            }
        });
    }

    /** Harus lebih besar dari isian lain (mis. berat maksimal > berat minimal). */
    public function lebihBesarDari(string $field, string $label): static
    {
        return $this->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get, $field, $label): void {
            $n = Desimal::parse($value);
            $lain = Desimal::parse($get($field));
            if ($n !== null && $lain !== null && $n <= $lain) {
                $fail('Nilai harus lebih besar dari '.$label.'.');
            }
        });
    }
}
