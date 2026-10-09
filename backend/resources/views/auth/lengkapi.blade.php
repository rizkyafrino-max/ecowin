@extends('auth.shell')

@section('title', 'Lengkapi data')

@section('card')
    <span class="ecl-kicker">DAFTAR NASABAH</span>
    <h2>Lengkapi data Anda</h2>
    <p class="ecl-sub">Data ini diperiksa petugas Bank Sampah sebelum akun Anda aktif sepenuhnya.</p>

    <div class="ecl-steps" aria-label="Langkah pendaftaran">
        <span>1. Akun Google</span><span class="on">2. Data diri</span><span>3. Petugas</span>
    </div>

    <div class="ecl-who">
        @if ($identity['avatar'])
            <img src="{{ $identity['avatar'] }}" alt="" referrerpolicy="no-referrer">
        @endif
        <div><b>{{ $identity['name'] ?? $identity['email'] }}</b><span>{{ $identity['email'] }} · terverifikasi Google</span></div>
    </div>

    <form method="POST" action="{{ route('daftar.simpan') }}" novalidate>
        @csrf

        <div class="ecl-field">
            <label for="nama">Nama lengkap</label>
            <input class="ecl-input" id="nama" name="nama" value="{{ old('nama', $form['nama'] ?? $identity['name']) }}" maxlength="150" required autocomplete="name">
            @error('nama')<p class="ecl-field-err" role="alert">{{ $message }}</p>@enderror
        </div>

        <div class="ecl-field">
            <label for="no_hp">Nomor HP</label>
            <input class="ecl-input" id="no_hp" name="no_hp" value="{{ old('no_hp', $form['no_hp'] ?? '') }}" inputmode="tel" placeholder="081234567890" required autocomplete="tel">
            <p class="ecl-hint">Gunakan nomor HP yang aktif agar petugas dapat menghubungi Anda.</p>
            @error('no_hp')<p class="ecl-field-err" role="alert">{{ $message }}</p>@enderror
        </div>

        <div class="ecl-field">
            <label for="alamat_rt_rw">Alamat (RT/RW)</label>
            <input class="ecl-input" id="alamat_rt_rw" name="alamat_rt_rw" value="{{ old('alamat_rt_rw', $form['alamat_rt_rw'] ?? '') }}" maxlength="255" placeholder="Jl. Melati No. 7, RT 01/RW 05" required autocomplete="street-address">
            @error('alamat_rt_rw')<p class="ecl-field-err" role="alert">{{ $message }}</p>@enderror
        </div>

        <div class="ecl-field">
            <label for="bank_sampah_id">Bank Sampah</label>
            <select class="ecl-input" id="bank_sampah_id" name="bank_sampah_id" required>
                <option value="">Pilih Bank Sampah RT/RW Anda</option>
                @foreach ($banks as $bank)
                    <option value="{{ $bank->id }}" @selected((string) old('bank_sampah_id', $form['bank_sampah_id'] ?? '') === (string) $bank->id)>{{ $bank->nama_bank_sampah }} · RT {{ $bank->rt }}/RW {{ $bank->rw }}</option>
                @endforeach
            </select>
            @error('bank_sampah_id')<p class="ecl-field-err" role="alert">{{ $message }}</p>@enderror
        </div>

        <label class="ecl-check">
            <input type="checkbox" name="setuju" value="1" @checked(old('setuju')) required>
            <span>Saya menyatakan data di atas benar dan bersedia diverifikasi oleh petugas Bank Sampah.</span>
        </label>
        @error('setuju')<p class="ecl-field-err" role="alert">{{ $message }}</p>@enderror

        <button type="submit" class="ecl-btn-primary">Daftar sekarang</button>
    </form>
@endsection
