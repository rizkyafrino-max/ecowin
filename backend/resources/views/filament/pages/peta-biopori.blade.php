<x-filament-panels::page>
    <style>
        .eco-biopori-page { display: flex; flex-direction: column; gap: 24px; color: #173126; }
        .eco-biopori-page section { box-sizing: border-box; }
        .eco-biopori-page .eco-hero { position: relative; overflow: hidden; border-radius: 24px; padding: 30px 34px; color: #fff; background: linear-gradient(135deg, #103f30 0%, #176b4d 55%, #23815e 100%); box-shadow: 0 14px 34px rgba(23,107,77,.16); }
        .eco-biopori-page .eco-hero h1 { margin: 8px 0 0; color: #fff; font-size: 28px; line-height: 1.2; font-weight: 800; }
        .eco-biopori-page .eco-hero p { margin: 8px 0 0; color: #d5f2e1; font-size: 14px; line-height: 1.6; max-width: 620px; }
        .eco-biopori-page .eco-hero .eyebrow { color: #b8ead0; font-size: 11px; font-weight: 800; letter-spacing: .18em; text-transform: uppercase; }
        .eco-biopori-page .eco-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
        .eco-biopori-page .eco-stat { min-height: 132px; padding: 20px; border: 1px solid #e2ece6; border-radius: 18px; background: #fff; box-shadow: 0 8px 24px rgba(23,64,42,.05); }
        .eco-biopori-page .eco-stat.active { border-color: #bcebd4; background: #effaf4; } .eco-biopori-page .eco-stat.full { border-color: #f6dfaa; background: #fff9e9; }
        .eco-biopori-page .eco-stat .label { color: #718279; font-size: 13px; } .eco-biopori-page .eco-stat.active .label { color: #176b4d; } .eco-biopori-page .eco-stat.full .label { color: #9a6800; }
        .eco-biopori-page .eco-stat .value { margin-top: 14px; color: #173126; font-size: 30px; line-height: 1; font-weight: 800; } .eco-biopori-page .eco-stat.active .value { color: #176b4d; } .eco-biopori-page .eco-stat.full .value { color: #9a6800; }
        .eco-biopori-page .eco-stat .hint { margin-top: 8px; color: #87978e; font-size: 11px; }
        .eco-biopori-page .eco-panel { overflow: hidden; border: 1px solid #e2ece6; border-radius: 22px; background: #fff; box-shadow: 0 8px 24px rgba(23,64,42,.05); }
        .eco-biopori-page #biopori-map { position: relative; z-index: 1 !important; }
        .eco-biopori-page #biopori-map .leaflet-pane, .eco-biopori-page #biopori-map .leaflet-control-container { z-index: 1 !important; }
        .eco-biopori-page #biopori-map .leaflet-top, .eco-biopori-page #biopori-map .leaflet-bottom { z-index: 2 !important; }
        .fi-sidebar, .fi-sidebar-ctn, .fi-sidebar-overlay { z-index: 1000 !important; }
        .eco-biopori-page .eco-panel-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 14px; padding: 20px 24px; border-bottom: 1px solid #edf2ef; }
        .eco-biopori-page .eco-panel-head h2 { margin: 0; color: #173126; font-size: 16px; font-weight: 800; } .eco-biopori-page .eco-panel-head p { margin: 4px 0 0; color: #718279; font-size: 13px; }
        .eco-biopori-page .eco-legend { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; color: #718279; font-size: 11px; } .eco-biopori-page .eco-legend i { display: inline-block; width: 9px; height: 9px; margin-right: 4px; border-radius: 50%; }
        .eco-biopori-page .eco-button { display: inline-block; padding: 10px 15px; border-radius: 11px; color: #fff; background: #176b4d; font-size: 12px; font-weight: 800; text-decoration: none; } .eco-biopori-page .eco-button:hover { background: #104b37; }
        .eco-biopori-page #biopori-map { width: 100%; height: 520px; background: #e9f5ee; }
        .eco-biopori-page .eco-table-wrap { overflow-x: auto; } .eco-biopori-page table { width: 100%; border-collapse: collapse; font-size: 13px; } .eco-biopori-page th { padding: 13px 18px; color: #718279; background: #f7faf8; font-size: 10px; letter-spacing: .08em; text-align: left; text-transform: uppercase; } .eco-biopori-page td { padding: 15px 18px; border-top: 1px solid #edf2ef; color: #53665c; } .eco-biopori-page tr:hover td { background: #f4faf6; } .eco-biopori-page td strong { color: #173126; } .eco-biopori-page td small { display: block; margin-top: 3px; color: #87978e; } .eco-biopori-page .status { display: inline-flex; padding: 5px 9px; border-radius: 99px; color: #176b4d; background: #e2f5ea; font-size: 11px; font-weight: 800; } .eco-biopori-page .map-link { color: #176b4d; font-weight: 800; text-decoration: none; }
        @media (max-width: 900px) { .eco-biopori-page .eco-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 640px) { .eco-biopori-page .eco-hero { padding: 24px 20px; } .eco-biopori-page .eco-hero h1 { font-size: 23px; } .eco-biopori-page .eco-stats { grid-template-columns: 1fr 1fr; gap: 10px; } .eco-biopori-page .eco-stat { min-height: 108px; padding: 15px; } .eco-biopori-page .eco-stat .value { font-size: 25px; } .eco-biopori-page #biopori-map { height: 390px; } .eco-biopori-page .eco-panel-head { padding: 17px; } }
    </style>
    @php
        $penuhCount = $points->where('status', 'penuh')->count();
        $dikosongkanCount = $points->where('status', 'dikosongkan')->count();
    @endphp
    <div class="eco-biopori-page">
        <section class="eco-hero">
            <div><p class="eyebrow">BioporiPrint</p><h1>Pantau titik Biopori di wilayahmu</h1><p>Kelola lokasi tanam, lihat sebaran titik di peta, dan pantau kondisi setiap Biopori dari satu halaman.</p></div>
            <div class="pointer-events-none absolute -right-8 -top-16 h-56 w-56 rounded-full border-[28px] border-white/10"></div><div class="pointer-events-none absolute -bottom-24 right-28 h-48 w-48 rounded-full bg-lime-300/10"></div>
        </section>
        <section class="eco-stats">
            <div class="eco-stat"><div class="label">Total titik</div><div class="value">{{ $points->count() }}</div><div class="hint">Lokasi dengan koordinat</div></div>
            <div class="eco-stat active"><div class="label">Titik aktif</div><div class="value">{{ $activeCount }}</div><div class="hint">Siap dipantau</div></div>
            <div class="eco-stat full"><div class="label">Penuh</div><div class="value">{{ $penuhCount }}</div><div class="hint">Perlu ditindaklanjuti</div></div>
            <div class="eco-stat"><div class="label">Jumlah pipa</div><div class="value">{{ $totalPipes }}</div><div class="hint">Dari seluruh titik</div></div>
        </section>
        <section class="eco-panel">
            <div class="eco-panel-head"><div><h2>Peta sebaran Biopori</h2><p>Klik penanda untuk melihat detail lokasi.</p></div><div class="eco-legend"><span><i style="background:#10b981"></i>Aktif</span><span><i style="background:#f59e0b"></i>Penuh</span><span><i style="background:#6b7280"></i>Dikosongkan</span><a href="{{ \App\Filament\Resources\TitikBioporis\TitikBioporiResource::getUrl('index') }}" class="eco-button">Kelola titik</a></div></div>
            <div id="biopori-map"></div>
        </section>
        <section class="eco-panel"><div class="eco-panel-head"><div><h2>Daftar lokasi</h2><p>{{ $points->count() }} titik tercatat di sistem.</p></div><span class="status">{{ $dikosongkanCount }} dikosongkan</span></div><div class="eco-table-wrap"><table><thead><tr><th>Nasabah</th><th>Lokasi</th><th>Tanam</th><th>Pipa</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
            @forelse ($points as $point)
                <tr><td><strong>{{ $point['nama'] }}</strong><small>{{ $point['bank'] }}</small></td><td>{{ $point['alamat'] }}<small>{{ $point['keterangan'] ?: 'Tidak ada keterangan' }}</small></td><td>{{ $point['tanggal'] }}</td><td>{{ $point['pipa'] }}</td><td><span class="status">{{ ucfirst($point['status']) }}</span></td><td><a class="map-link" target="_blank" rel="noreferrer" href="https://www.openstreetmap.org/?mlat={{ $point['lat'] }}&mlon={{ $point['lng'] }}#map=18/{{ $point['lat'] }}/{{ $point['lng'] }}">Lihat peta ↗</a></td></tr>
            @empty
                <tr><td colspan="6" class="px-5 py-14 text-center"><div class="text-4xl">⌖</div><p class="mt-3 font-semibold text-gray-900">Belum ada titik Biopori</p><p class="mt-1 text-sm text-gray-500">Tambahkan titik dengan koordinat untuk mulai memetakan wilayah.</p></td></tr>
            @endforelse
            </tbody></table></div></section>
    </div>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => { const points = @js($points); const mapNode = document.getElementById('biopori-map'); if (!mapNode || !window.L) return; const center = points.length ? [points[0].lat, points[0].lng] : [-2.5489, 118.0149]; const map = L.map(mapNode, { zoomControl: true }).setView(center, points.length ? 14 : 5); L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map); const markers = []; points.forEach(point => { const color = point.status === 'aktif' ? '#10b981' : (point.status === 'penuh' ? '#f59e0b' : '#6b7280'); const marker = L.circleMarker([point.lat, point.lng], { radius: 9, color: '#fff', weight: 3, fillColor: color, fillOpacity: 1 }).addTo(map); marker.bindPopup(`<div style="min-width:190px"><strong style="font-size:14px">${escapeHtml(point.nama)}</strong><br><span>${escapeHtml(point.alamat)}</span><br><span>${escapeHtml(point.bank)}</span><br><span>Status: ${escapeHtml(point.status)} · ${point.pipa} pipa</span><br><a target="_blank" href="https://www.openstreetmap.org/?mlat=${point.lat}&mlon=${point.lng}#map=18/${point.lat}/${point.lng}">Buka navigasi</a></div>`); markers.push(marker); }); if (markers.length > 1) map.fitBounds(L.featureGroup(markers).getBounds().pad(0.15)); setTimeout(() => map.invalidateSize(), 150); function escapeHtml(value) { return String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char])); } });
    </script>
</x-filament-panels::page>
