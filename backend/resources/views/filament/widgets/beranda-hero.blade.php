<x-filament-widgets::widget>
    <div class="eco-home">
    @if (count($popup) > 0)
        {{-- Pengingat tugas menunggu: muncul saat Beranda dibuka, dan muncul lagi bila jumlah tugas bertambah. --}}
        <div x-data="{
                open: false,
                init() {
                    try {
                        const k = 'eco-popup-{{ $user->id }}';
                        const last = parseInt(sessionStorage.getItem(k) || '0');
                        if ({{ $totalPending }} > last) { this.open = true; sessionStorage.setItem(k, {{ $totalPending }}); }
                    } catch (e) { this.open = true; }
                },
             }"
             
             x-show="open" x-cloak x-transition.opacity @keydown.escape.window="open = false"
             class="eco-popup" role="dialog" aria-modal="true" aria-labelledby="eco-popup-title">
            <div class="eco-popup-card" @click.outside="open = false">
                <span class="eco-popup-ico"><x-filament::icon icon="heroicon-o-bell-alert" class="eco-ico" /></span>
                <h2 id="eco-popup-title">Ada {{ $totalPending }} tugas menunggu</h2>
                <p>Jangan sampai terlewat, nasabah dan petugas lain menunggu tindakan Anda.</p>
                <ul>
                    @foreach ($popup as $p)
                        <li>
                            <a href="{{ $p['url'] }}">
                                <span><b>{{ $p['label'] }}</b><small>{{ $p['hint'] }}</small></span>
                                <em>{{ $p['count'] }}</em>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <button type="button" @click="open = false">Nanti saja</button>
            </div>
        </div>
    @endif
        {{-- Sapaan, pencarian, notifikasi --}}
        <header class="eco-home-top">
            <div class="eco-home-user">
                @if ($user->avatar)
                    <img class="eco-avatar" src="{{ $user->avatar }}" alt="" referrerpolicy="no-referrer">
                @else
                    <span class="eco-avatar eco-avatar-text" aria-hidden="true">{{ mb_strtoupper(mb_substr((string) $user->nama, 0, 1)) }}</span>
                @endif
                <div>
                    <p class="eco-home-name">{{ $sapaan }}, {{ $user->nama }}</p>
                    <p class="eco-home-loc"><span class="eco-role">{{ strtoupper($user->roleEnum()?->label() ?? $user->role) }}</span>{{ $lokasi }}</p>
                </div>
            </div>
            <div class="eco-home-tools">
                <form class="eco-search" method="GET" action="{{ $urlNasabah }}" role="search">
                    <x-filament::icon icon="heroicon-o-magnifying-glass" class="eco-ico" />
                    <input type="search" name="tableSearch" placeholder="Cari nasabah…" aria-label="Cari nasabah" autocomplete="off">
                </form>
                <a class="eco-icon-btn" href="{{ $urlPending ?? '#' }}" aria-label="Tugas menunggu: {{ $totalPending }}">
                    <x-filament::icon icon="heroicon-o-bell" class="eco-ico" />
                    @if ($totalPending > 0)<span class="eco-dot">{{ $totalPending > 99 ? '99+' : $totalPending }}</span>@endif
                </a>
            </div>
        </header>

        {{-- Ringkasan utama: carousel banner --}}
        @php
            $slides = [
                ['badge' => 'Saldo', 'label' => $user->isAdmin() ? 'Total saldo seluruh nasabah' : 'Total saldo nasabah', 'value' => 'Rp '.number_format($kpi['saldo'], 0, ',', '.'), 'note' => 'Saldo beredar di '.number_format($kpi['nasabah'], 0, ',', '.').' nasabah aktif', 'cta' => 'Lihat nasabah', 'url' => $urlNasabah, 'icon' => 'heroicon-o-banknotes'],
                ['badge' => 'Hari ini', 'label' => 'Setoran hari ini', 'value' => number_format($kpi['setoran_hari_ini'], 0, ',', '.').' transaksi', 'note' => number_format($kpi['berat'], 1, ',', '.').' kg sampah anorganik terkumpul', 'cta' => 'Catat setoran', 'url' => $urlSetoran, 'icon' => 'heroicon-o-archive-box'],
                ['badge' => $totalPending > 0 ? 'Perlu tindakan' : 'Beres', 'label' => 'Menunggu persetujuan', 'value' => $totalPending.' tugas', 'note' => 'Biopori '.$pending['biopori'].' · Penarikan '.$pending['penarikan'].' · Koreksi '.$pending['koreksi'], 'cta' => $totalPending > 0 ? 'Tinjau sekarang' : 'Lihat laporan', 'url' => $urlPending ?? $urlLaporan, 'icon' => 'heroicon-o-bell-alert'],
                ['badge' => 'Komunitas', 'label' => 'Nasabah aktif', 'value' => number_format($kpi['nasabah'], 0, ',', '.').' nasabah', 'note' => $user->isAdmin() ? 'Terdaftar di seluruh Bank Sampah' : 'Terdaftar di Bank Sampah Anda', 'cta' => 'Lihat laporan', 'url' => $urlLaporan, 'icon' => 'heroicon-o-users'],
            ];
        @endphp
        <section aria-label="Ringkasan" x-data="{
            i: 0, n: {{ count($slides) }}, t: null,
            go(k){ this.i = (k + this.n) % this.n; const el = this.$refs.track; el.scrollTo({ left: el.children[this.i].offsetLeft - el.offsetLeft, behavior: 'smooth' }) },
            sync(){ const el = this.$refs.track; let b = 0, d = 1e9; [...el.children].forEach((c, k) => { const x = Math.abs(c.offsetLeft - el.offsetLeft - el.scrollLeft); if (x < d) { d = x; b = k } }); this.i = b },
            play(){ this.stop(); this.t = setInterval(() => this.go(this.i + 1), 6000) },
            stop(){ clearInterval(this.t) }
        }" x-init="play()" @mouseenter="stop()" @mouseleave="play()">
            <div class="eco-slides" x-ref="track" @scroll.debounce.80ms="sync()">
                @foreach ($slides as $s)
                    <article class="eco-slide">
                        <div class="eco-slide-text">
                            <span class="eco-slide-badge">{{ $s['badge'] }}</span>
                            <p class="eco-slide-label">{{ $s['label'] }}</p>
                            <p class="eco-slide-value">{{ $s['value'] }}</p>
                            <p class="eco-slide-note">{{ $s['note'] }}</p>
                            <a class="eco-slide-cta" href="{{ $s['url'] }}">{{ $s['cta'] }}</a>
                        </div>
                        <div class="eco-slide-art" aria-hidden="true">
                            <span class="eco-art-ring eco-art-ring-1"></span>
                            <span class="eco-art-ring eco-art-ring-2"></span>
                            <span class="eco-art-core"><x-filament::icon :icon="$s['icon']" class="eco-art-icon" /></span>
                            <svg class="eco-leaf eco-leaf-1" viewBox="0 0 24 24"><path d="M5 19c0-8 5-14 14-14 0 9-6 14-14 14Z" fill="currentColor"/><path d="M5 19 13 11" stroke="rgba(0,0,0,.25)" stroke-width="1.2" fill="none" stroke-linecap="round"/></svg>
                            <svg class="eco-leaf eco-leaf-2" viewBox="0 0 24 24"><path d="M5 19c0-8 5-14 14-14 0 9-6 14-14 14Z" fill="currentColor"/></svg>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="eco-dots" role="tablist" aria-label="Pilih slide">
                @foreach ($slides as $k => $s)
                    <button type="button" role="tab" :aria-selected="i === {{ $k }}" :class="i === {{ $k }} ? 'is-on' : ''" @click="go({{ $k }}); play()" aria-label="Slide {{ $k + 1 }}: {{ $s['label'] }}"></button>
                @endforeach
            </div>
        </section>

        {{-- Akses cepat --}}
        @php $kolom = count($menu) <= 6 ? count($menu) : (int) ceil(count($menu) / 2); @endphp
        <section aria-label="Akses cepat">
            <div class="eco-sec-head"><h2>Akses cepat</h2></div>
            <div class="eco-cats" style="--cols: {{ $kolom }}">
                @foreach ($menu as $m)
                    <a class="eco-cat" href="{{ $m['url'] }}">
                        <span class="eco-cat-ico"><x-filament::icon :icon="$m['icon']" class="eco-ico" /></span>
                        <span>{{ $m['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>

        <div class="eco-cols">
            {{-- Jenis sampah --}}
            <section aria-label="Jenis sampah teratas">
                <div class="eco-sec-head"><h2>Jenis sampah teratas</h2><a href="{{ \App\Filament\Resources\HargaSampahs\HargaSampahResource::getUrl('index') }}">Lihat semua</a></div>
                <div class="eco-items">
                    @forelse ($jenis as $j)
                        <article class="eco-item">
                            <span class="eco-item-badge">{{ $j['kategori'] }}</span>
                            <h3>{{ $j['nama'] }}</h3>
                            <p class="eco-item-price">{{ $j['harga'] }}</p>
                            <p class="eco-item-meta">{{ $j['satuan'] }}</p>
                            <p class="eco-item-meta">{{ $j['berat'] }}</p>
                            @if ($j['url'])
                                <a class="eco-plus" href="{{ $j['url'] }}" aria-label="Catat setoran {{ $j['nama'] }}">
                                    <x-filament::icon icon="heroicon-m-plus" class="eco-ico" />
                                </a>
                            @endif
                        </article>
                    @empty
                        <p class="eco-empty">Belum ada jenis sampah aktif.</p>
                    @endforelse
                </div>
            </section>

            {{-- Peringkat --}}
            <section aria-label="{{ $deretan['judul'] }}">
                <div class="eco-sec-head"><h2>{{ $deretan['judul'] }}</h2><a href="{{ $deretan['url'] }}">Lihat semua</a></div>
                <div class="eco-panel">
                    <div class="eco-rank">
                        @forelse ($deretan['items'] as $it)
                            <div class="eco-rank-row">
                                <span class="eco-rank-no">{{ $loop->iteration }}</span>
                                <div class="eco-rank-body">
                                    <p class="eco-rank-name">{{ $it['nama'] }}</p>
                                    <div class="eco-rank-bar"><i style="width:{{ $it['persen'] }}%"></i></div>
                                </div>
                                <span class="eco-rank-val">{{ $it['nilai'] }}</span>
                            </div>
                        @empty
                            <p class="eco-empty">Belum ada data.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-filament-widgets::widget>
