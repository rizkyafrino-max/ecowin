@extends('auth.shell')

@section('title', 'Lengkapi data')

@section('rootClass', 'ecl-reg')

@section('card')
    @include('auth.stepper', ['aktif' => 2])
    <span class="ecl-kicker">DAFTAR NASABAH</span>
    <h2>Lengkapi data Anda</h2>
    <p class="ecl-sub">Data ini diperiksa petugas Bank Sampah sebelum akun Anda aktif sepenuhnya.</p>

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

        <fieldset class="ecl-field ecl-banks">
            <legend>Pilih Bank Sampah RT/RW Anda</legend>
            @php($terpilih = (string) old('bank_sampah_id', $form['bank_sampah_id'] ?? ''))
            @foreach ($banks as $bank)
                <label class="ecl-bank">
                    <input type="radio" name="bank_sampah_id" value="{{ $bank->id }}" @checked($terpilih === (string) $bank->id) required>
                    <span class="ecl-bank-ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4M10 10h4M10 14h4M10 18h4"/></svg></span>
                    <span class="ecl-bank-txt"><b>{{ $bank->nama_bank_sampah }}</b><small>RT {{ $bank->rt }} / RW {{ $bank->rw }}</small></span>
                    <span class="ecl-bank-tick" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7"/></svg></span>
                </label>
            @endforeach
            @error('bank_sampah_id')<p class="ecl-field-err" role="alert">{{ $message }}</p>@enderror
        </fieldset>

        <label class="ecl-check">
            <input type="checkbox" name="setuju" value="1" @checked(old('setuju')) required>
            <span>Saya menyatakan data di atas benar dan bersedia diverifikasi oleh petugas Bank Sampah.</span>
        </label>
        @error('setuju')<p class="ecl-field-err" role="alert">{{ $message }}</p>@enderror

        <div class="ecl-submitbar">
            <button type="submit" class="ecl-btn-primary">Daftar sekarang</button>
        </div>
    </form>
@endsection
