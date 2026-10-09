@unless (request()->is('admin/login'))
@php
    $path = request()->path();
    $items = [
        ['label' => 'Beranda', 'href' => '/admin', 'active' => $path === 'admin', 'icon' => '<path d="m3 10 9-7 9 7"/><path d="M5 9v11h14V9"/><path d="M9 20v-6h6v6"/>'],
        ['label' => 'Nasabah', 'href' => '/admin/nasabahs', 'active' => str_starts_with($path, 'admin/nasabahs'), 'icon' => '<circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0"/><path d="M16 5.5a3 3 0 0 1 0 5.8M18 14a5 5 0 0 1 3 4.6"/>'],
        ['label' => 'Transaksi', 'href' => '/admin/transaksi-anorganiks', 'active' => str_starts_with($path, 'admin/transaksi-'), 'icon' => '<path d="M7 3v18M7 3 3 7m4-4 4 4M17 21V3m0 18 4-4m-4 4-4-4"/>'],
        ['label' => 'Penarikan', 'href' => '/admin/penarikan-saldos', 'active' => str_starts_with($path, 'admin/penarikan-saldos'), 'icon' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h3"/>'],
    ];
@endphp
<nav class="eco-footer" aria-label="Navigasi cepat EcoWin">
    @foreach ($items as $item)
        <a class="eco-footer-link {{ $item['active'] ? 'is-active' : '' }}" href="{{ $item['href'] }}">
            <svg viewBox="0 0 24 24" aria-hidden="true">{!! $item['icon'] !!}</svg>
            <span>{{ $item['label'] }}</span>
        </a>
        @if ($loop->iteration === 2)
            <a class="eco-footer-link eco-footer-scan {{ str_starts_with($path, 'admin/scan-kartu') ? 'is-active' : '' }}" href="/admin/scan-kartu" aria-label="Pindai kartu QR">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4z"/><path d="M14 14h2v2h-2zM18 14h2M14 18v2M18 18h2v2h-2z"/></svg>
            </a>
        @endif
    @endforeach
</nav>
<script>
    (() => {
        const footer = document.querySelector('.eco-footer');
        if (!footer || footer.dataset.scrollReady) return;
        footer.dataset.scrollReady = '1';
        let lastScroll = window.scrollY;
        let stopTimer;
        const show = () => footer.classList.remove('is-hidden');
        const hide = () => footer.classList.add('is-hidden');
        window.addEventListener('scroll', () => {
            const current = window.scrollY;
            if (current < 40 || current < lastScroll - 6) show();
            else if (current > lastScroll + 6) hide();
            lastScroll = current;
            clearTimeout(stopTimer);
            stopTimer = setTimeout(show, 420);
        }, { passive: true });
    })();
</script>
@endunless
@unless (request()->is('admin/login'))
<script>
    // Tabel di HP tampil sebagai kartu: salin judul kolom ke tiap sel (data-label).
    (() => {
        if (window.__ecoLabels) return;
        window.__ecoLabels = true;
        const apply = () => {
            document.querySelectorAll('table.fi-ta-table').forEach((table) => {
                const labels = [...table.querySelectorAll('thead th')].map((th) => (th.innerText || '').trim().replace(/\s+/g, ' '));
                table.querySelectorAll('tbody tr').forEach((tr) => {
                    [...tr.children].forEach((td, i) => {
                        const label = labels[i] || '';
                        if (td.dataset.label !== label) td.dataset.label = label;
                    });
                });
            });
        };
        let timer;
        const schedule = () => { clearTimeout(timer); timer = setTimeout(apply, 60); };
        new MutationObserver(schedule).observe(document.body, { childList: true, subtree: true });
        document.addEventListener('DOMContentLoaded', apply);
        apply();
    })();
</script>
@endunless
