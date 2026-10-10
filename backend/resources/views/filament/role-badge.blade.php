@php $ecoUser = auth()->user(); @endphp
@if ($ecoUser)
    <span class="eco-role-badge" role="status" aria-label="Peran aktif: {{ $ecoUser->roleEnum()?->label() }}">
        {{ strtoupper($ecoUser->roleEnum()?->label() ?? $ecoUser->role) }}
        @if ($ecoUser->isPetugas() && $ecoUser->bankSampah)
            <small>{{ $ecoUser->bankSampah->nama_bank_sampah }}</small>
        @endif
    </span>
@endif
