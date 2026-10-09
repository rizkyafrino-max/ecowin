@php
    // Parameter dari aplikasi web Nasabah (PKCE challenge) diteruskan ke Google & pendaftaran.
    $challenge = request()->query('c');
    $flow = array_filter([
        'app' => request()->query('app') === 'web' ? 'web' : null,
        'c' => \App\Services\Auth\WebLoginHandoff::validChallenge($challenge) ? $challenge : null,
    ]);
@endphp
<div class="ecl-root">
    @include('auth.styles')

    <div class="ecl-bg" aria-hidden="true"></div>

    @include('auth.brand')

    <main class="ecl-form">
        <div class="ecl-card">
            <span class="ecl-kicker">MASUK KE ECOWIN</span>

            <h2>Selamat datang</h2>
            <p class="ecl-sub">Satu halaman masuk untuk Admin, Petugas, dan Nasabah.</p>

            @if (session('auth_error'))
                <div class="ecl-err" role="alert">{{ session('auth_error') }}</div>
            @endif

            <a href="{{ route('auth.google.redirect', $flow) }}" class="ecl-btn">
                <svg viewBox="0 0 48 48" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/><path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/></svg>
                <span>Continue with Google</span>
            </a>

            <p class="ecl-note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 5 6v5c0 5 3 8.5 7 10 4-1.5 7-5 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                <span>Gunakan akun Google yang telah terverifikasi untuk mengakses EcoWin.</span>
            </p>

            <div class="ecl-roles">
                <span>Masuk sebagai</span>
                <span class="ecl-pill ecl-pill-admin">ADMIN</span>
                <span class="ecl-pill ecl-pill-petugas">PETUGAS</span>
                <span class="ecl-pill" style="background:#DCFCE7;color:#15803D">NASABAH</span>
            </div>

            <p class="ecl-or">Belum punya akun? <a class="ecl-link" href="{{ route('daftar', $flow) }}">Daftar sebagai nasabah</a></p>

            <p class="ecl-foot">© {{ date('Y') }} EcoWin · Bank Sampah Digital Berkelanjutan</p>
        </div>
    </main>

    @include('auth.features')
</div>
