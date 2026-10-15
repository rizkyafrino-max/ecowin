@php
    $formState = \Illuminate\Support\Str::beforeLast($getStatePath(), '.');
    $latitudePath = $formState.'.latitude';
    $longitudePath = $formState.'.longitude';
    $mapId = 'biopori-picker-'.md5($getStatePath());
@endphp

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<div
    x-data="{
        latitude: $wire.$entangle(@js($latitudePath)),
        longitude: $wire.$entangle(@js($longitudePath)),
        status: 'Klik peta untuk menentukan posisi, atau gunakan lokasi perangkat.',
        map: null,
        marker: null,
        init() {
            this.$nextTick(() => {
                if (!window.L || !document.getElementById(@js($mapId))) {
                    this.status = 'Peta belum dapat dimuat. Isi koordinat secara manual.';
                    return;
                }
                const hasPoint = this.latitude !== null && this.latitude !== '' && this.longitude !== null && this.longitude !== '';
                const center = hasPoint ? [Number(this.latitude), Number(this.longitude)] : [-2.5489, 118.0149];
                this.map = L.map(@js($mapId)).setView(center, hasPoint ? 16 : 5);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(this.map);
                this.map.on('click', event => this.setPoint(event.latlng.lat, event.latlng.lng, 'Koordinat terpilih dari peta.'));
                if (hasPoint) this.syncMarker();
                this.$watch('latitude', () => this.syncMarker());
                this.$watch('longitude', () => this.syncMarker());
                setTimeout(() => this.map?.invalidateSize(), 200);
            });
        },
        setPoint(latitude, longitude, message) {
            this.latitude = Number(latitude).toFixed(7);
            this.longitude = Number(longitude).toFixed(7);
            this.status = message;
            this.syncMarker();
            this.map?.setView([Number(this.latitude), Number(this.longitude)], Math.max(this.map.getZoom(), 16));
        },
        syncMarker() {
            if (!this.map || !this.latitude || !this.longitude) return;
            const position = [Number(this.latitude), Number(this.longitude)];
            if (!this.marker) this.marker = L.marker(position, { draggable: true }).addTo(this.map);
            else this.marker.setLatLng(position);
            this.marker.off('dragend').on('dragend', event => {
                const position = event.target.getLatLng();
                this.setPoint(position.lat, position.lng, 'Penanda digeser. Koordinat berhasil diperbarui.');
            });
        },
        watchId: null,
        circle: null,
        berhenti() {
            if (this.watchId !== null) { navigator.geolocation.clearWatch(this.watchId); this.watchId = null; }
        },
        ambil() {
            if (!navigator.geolocation) {
                this.status = 'Perangkat tidak mendukung akses lokasi. Pilih titik di peta atau isi manual.';
                return;
            }
            this.berhenti();
            this.status = 'Meminta izin lokasi…';
            let terbaik = null;
            const selesai = () => {
                this.berhenti();
                if (!terbaik) return;
                const a = Math.round(terbaik.accuracy);
                this.status = a > 100
                    ? `Akurasi rendah (±${a} m): perangkat ini kemungkinan memakai perkiraan WiFi/IP, bukan GPS. Geser penanda ke posisi yang benar atau coba dari HP.`
                    : `Lokasi terisi (akurasi ±${a} m). Anda masih bisa menggeser penanda atau mengedit koordinat.`;
            };
            // Pembacaan pertama sering kasar; ikuti beberapa detik dan simpan yang paling akurat.
            this.watchId = navigator.geolocation.watchPosition(
                posisi => {
                    const c = posisi.coords;
                    if (terbaik && c.accuracy >= terbaik.accuracy) return;
                    terbaik = { accuracy: c.accuracy };
                    this.setPoint(c.latitude, c.longitude, `Mencari sinyal terbaik… (akurasi ±${Math.round(c.accuracy)} m)`);
                    if (this.circle) this.map.removeLayer(this.circle);
                    this.circle = L.circle([c.latitude, c.longitude], { radius: c.accuracy, weight: 1, color: '#176b4d', fillOpacity: 0.12 }).addTo(this.map);
                    if (c.accuracy <= 20) selesai();
                },
                error => {
                    this.berhenti();
                    this.status = error.code === 1 ? 'Izin lokasi ditolak. Anda tetap dapat memilih titik di peta atau mengisi manual.' : 'Lokasi belum tersedia. Pilih titik di peta atau isi manual.';
                },
                { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 }
            );
            setTimeout(selesai, 15000);
        }
    }"
    class="biopori-location-picker"
>
    <div class="biopori-location-picker__header">
        <div><strong>Pilih posisi titik Biopori</strong><span>Klik peta atau geser penanda ke lokasi yang tepat.</span></div>
        <button type="button" x-on:click="ambil">Gunakan lokasi saya</button>
    </div>
    <div id="{{ $mapId }}" class="biopori-location-picker__map"></div>
    <p class="biopori-location-picker__status" x-text="status"></p>
</div>

<style>
    .biopori-location-picker{overflow:hidden;border:1px solid #d8e9df;border-radius:16px;background:#f8fcf9}
    .biopori-location-picker__header{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px}
    .biopori-location-picker__header strong,.biopori-location-picker__header span{display:block}
    .biopori-location-picker__header strong{color:#174c37;font-size:13px;font-weight:800}
    .biopori-location-picker__header span{margin-top:3px;color:#718279;font-size:11px}
    .biopori-location-picker__header button{flex:none;border:0;border-radius:10px;background:#176b4d;padding:9px 13px;color:#fff;font-size:11px;font-weight:800;cursor:pointer}
    .biopori-location-picker__header button:hover{background:#104b37}
    .biopori-location-picker__map{height:320px;width:100%;background:#e9f5ee}
    .biopori-location-picker__status{margin:0;padding:10px 16px;color:#527363;font-size:11px}
    @media(max-width:640px){.biopori-location-picker__header{align-items:flex-start;flex-direction:column}.biopori-location-picker__map{height:280px}}
</style>
