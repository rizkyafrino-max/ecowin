<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\BankSampah;
use App\Services\AuditLogger;
use App\Services\Auth\GoogleIdentity;
use App\Services\Auth\WebLoginHandoff;
use App\Services\NasabahService;
use App\Support\Phone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Pendaftaran Nasabah mandiri: Google (email terverifikasi) -> data diri -> akun dibuat dengan status verifikasi `pending` sampai disetujui Petugas.
 * Email & Google ID berasal dari sesi server (bukan form). Role selalu nasabah.
 */
class RegistrationController extends Controller
{
    public function __construct(
        private NasabahService $nasabah,
        private WebLoginHandoff $handoff,
        private AuditLogger $audit,
    ) {}

    /** Halaman awal: tombol "Daftar dengan Google" (meneruskan PKCE challenge dari aplikasi web bila ada). */
    public function show(Request $request): View
    {
        $challenge = $request->query('c');

        return view('auth.daftar', [
            'googleUrl' => route('auth.google.redirect', array_filter([
                'intent' => 'register',
                'app' => $request->query('app') === 'web' ? 'web' : null,
                'c' => WebLoginHandoff::validChallenge($challenge) ? $challenge : null,
            ])),
        ]);
    }

    public function lengkapi(Request $request): View|RedirectResponse
    {
        if (! ($pending = $this->pending($request))) {
            return $this->kadaluarsa();
        }

        return view('auth.lengkapi', [
            'identity' => $pending['identity'],
            'form' => $pending['form'] ?? [],
            'banks' => BankSampah::query()->where('status', 'aktif')->orderBy('nama_bank_sampah')->get(['id', 'nama_bank_sampah', 'rt', 'rw']),
        ]);
    }

    /** Validasi data diri lalu buat akun Nasabah berstatus pending (menunggu verifikasi petugas). */
    public function simpan(Request $request): RedirectResponse
    {
        if (! ($pending = $this->pending($request))) {
            return $this->kadaluarsa();
        }

        $request->merge(['no_hp' => Phone::normalize($request->input('no_hp')) ?? $request->input('no_hp')]);

        $data = $request->validate([
            'nama' => ['required', 'string', 'min:3', 'max:150'],
            'no_hp' => ['required', 'string', 'regex:/^08[0-9]{7,12}$/', Rule::unique('nasabah', 'no_hp')],
            'alamat_rt_rw' => ['required', 'string', 'min:5', 'max:255'],
            'bank_sampah_id' => ['required', 'integer', Rule::exists('bank_sampah', 'id')->where('status', 'aktif')],
            'setuju' => ['accepted'],
        ], [
            'no_hp.regex' => 'Nomor HP tidak valid (contoh 081234567890).',
            'no_hp.unique' => 'Nomor HP sudah terdaftar.',
            'bank_sampah_id.exists' => 'Pilih Bank Sampah yang tersedia.',
            'setuju.accepted' => 'Anda harus menyetujui ketentuan.',
        ]);
        unset($data['setuju']);

        $id = $pending['identity'];
        $identity = new GoogleIdentity($id['google_id'], $id['email'], true, $id['name'], $id['avatar']);

        try {
            $nasabah = $this->nasabah->daftarMandiri($identity, $data);
        } catch (ValidationException $e) {
            $request->session()->forget('pending_registration');

            return redirect()->route('filament.admin.auth.login')->with('auth_error', collect($e->errors())->flatten()->first());
        }

        $request->session()->forget('pending_registration');
        $request->session()->regenerate();

        $user = $nasabah->user;
        $this->audit->log('daftar_mandiri', $nasabah, null, ['email' => $user->email, 'bank_sampah_id' => $nasabah->bank_sampah_id], $user);

        if ($pending['challenge']) {
            return redirect()->away($this->handoff->redirectUrl($this->handoff->issue($user, $pending['challenge'])));
        }

        return redirect()->away(config('ecowin.web_url').'/masuk');
    }

    private function kadaluarsa(): RedirectResponse
    {
        return redirect()->route('daftar')->with('auth_error', 'Sesi pendaftaran berakhir. Mulai lagi dengan Google.');
    }

    /**
     * @return array{identity: array<string, mixed>, challenge: ?string, form?: array<string, mixed>}|null
     */
    private function pending(Request $request): ?array
    {
        $pending = $request->session()->get('pending_registration');

        if (! is_array($pending) || ($pending['expires_at'] ?? 0) < now()->timestamp) {
            $request->session()->forget('pending_registration');

            return null;
        }

        return $pending;
    }
}
