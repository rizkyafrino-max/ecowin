@php
    $aktif = $aktif ?? 1;
    $langkah = ['Akun Google', 'Data diri', 'Petugas'];
@endphp
<ol class="ecl-stepper" aria-label="Langkah pendaftaran">
    @foreach ($langkah as $i => $teks)
        @php($n = $i + 1)
        <li class="{{ $n < $aktif ? 'done' : ($n === $aktif ? 'on' : '') }}" @if ($n === $aktif) aria-current="step" @endif>
            <i aria-hidden="true">
                @if ($n < $aktif)
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7"/></svg>
                @else
                    {{ $n }}
                @endif
            </i>
            <span>{{ $teks }}</span>
        </li>
    @endforeach
</ol>
