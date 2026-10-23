@php($u = auth()->user())
<div style="display:flex;align-items:center;gap:1rem">
    @if ($u?->avatarUrl())
        <img src="{{ $u->avatarUrl() }}" alt="Foto profil saat ini" width="88" height="88" referrerpolicy="no-referrer"
             style="width:88px;height:88px;border-radius:999px;object-fit:cover;box-shadow:0 0 0 4px #fff,0 0 0 5px #E2E8F0,0 10px 24px -12px rgba(15,23,42,.4)">
    @else
        <span aria-hidden="true" style="width:88px;height:88px;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;background:var(--eco-soft);color:var(--eco-primary);font-size:2rem;font-weight:800">{{ mb_strtoupper(mb_substr((string) $u?->nama, 0, 1)) }}</span>
    @endif
    <div>
        <div style="font-weight:800;font-size:1.05rem">{{ $u?->nama }}</div>
        <div style="color:#64748B;font-size:.85rem">{{ $u?->foto_profil ? 'Foto sendiri' : ($u?->jenis_kelamin ? 'Avatar bawaan' : 'Belum ada foto') }}</div>
    </div>
</div>
