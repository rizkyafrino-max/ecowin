package id.ecowin.app

import android.Manifest
import android.annotation.SuppressLint
import android.content.Context
import android.content.pm.PackageManager
import android.location.Location
import android.location.LocationListener
import android.location.LocationManager
import android.os.Bundle
import android.view.MotionEvent
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Icon
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.core.content.ContextCompat
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.LifecycleEventObserver
import androidx.lifecycle.compose.LocalLifecycleOwner
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import org.osmdroid.config.Configuration
import org.osmdroid.events.MapEventsReceiver
import org.osmdroid.tileprovider.tilesource.TileSourceFactory
import org.osmdroid.util.GeoPoint
import org.osmdroid.views.MapView
import org.osmdroid.views.overlay.MapEventsOverlay
import org.osmdroid.views.overlay.Marker

/**
 * Nasabah mengajukan titik Biopori baru. Posisi dipilih dengan GPS perangkat ("Gunakan lokasi saya")
 * atau mengetuk peta lalu menggeser penanda. Titik berstatus "Menunggu" sampai disetujui petugas.
 * Izin lokasi hanya diminta saat tombol lokasi diketuk, dan lokasi tidak dikirim ke mana pun
 * selain sebagai koordinat titik yang diajukan.
 */
@Composable
fun AjukanTitikForm(client: ApiClient, onDone: () -> Unit, onCancel: () -> Unit) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    var nama by rememberSaveable { mutableStateOf("") }
    var keterangan by rememberSaveable { mutableStateOf("") }
    var lat by rememberSaveable { mutableStateOf<Double?>(null) }
    var lng by rememberSaveable { mutableStateOf<Double?>(null) }
    var info by remember { mutableStateOf("Ketuk peta untuk menandai lokasi, atau gunakan lokasi perangkat.") }
    var mencari by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }
    var busy by remember { mutableStateOf(false) }

    fun pilih(la: Double, lo: Double, pesan: String?) {
        lat = la
        lng = lo
        error = null
        if (pesan != null) info = pesan
    }

    val gps = remember { GpsFix(context) }
    DisposableEffect(Unit) { onDispose { gps.stop() } }

    fun mulaiCari() {
        mencari = true
        info = "Mencari sinyal terbaik…"
        scope.launch {
            val hasil = gps.cari(
                onUpdate = { l ->
                    pilih(l.latitude, l.longitude, "Akurasi ±${l.accuracy.toInt()} m" + if (l.accuracy > 100) ". Kurang akurat: geser penanda ke posisi yang benar." else ". Anda masih bisa menggeser penanda.")
                },
            )
            mencari = false
            if (!hasil) info = "Lokasi belum tersedia. Pastikan GPS aktif, atau ketuk peta untuk menandai posisi."
        }
    }

    val izin = rememberLauncherForActivityResult(ActivityResultContracts.RequestMultiplePermissions()) { hasil ->
        if (hasil.values.any { it }) mulaiCari()
        else info = "Izin lokasi ditolak. Anda tetap dapat mengetuk peta untuk menandai posisi."
    }

    fun gunakanLokasi() {
        val sudah = listOf(Manifest.permission.ACCESS_FINE_LOCATION, Manifest.permission.ACCESS_COARSE_LOCATION)
            .any { ContextCompat.checkSelfPermission(context, it) == PackageManager.PERMISSION_GRANTED }
        if (sudah) mulaiCari()
        else izin.launch(arrayOf(Manifest.permission.ACCESS_FINE_LOCATION, Manifest.permission.ACCESS_COARSE_LOCATION))
    }

    EcoCard {
        Text("Ajukan titik Biopori baru", fontWeight = FontWeight.Bold)
        Text("Titik diperiksa petugas Bank Sampah. Setelah disetujui, titik tampil di peta dan bisa dipakai untuk melapor.", color = Slate500, fontSize = 12.sp)

        OutlinedTextField(
            nama, { nama = it.take(100) }, label = { Text("Nama lokasi") }, placeholder = { Text("Contoh: Pojok taman RT 03") },
            singleLine = true, shape = RoundedCornerShape(14.dp), modifier = Modifier.fillMaxWidth(),
        )
        OutlinedTextField(
            keterangan, { keterangan = it.take(255) }, label = { Text("Keterangan (opsional)") },
            minLines = 2, shape = RoundedCornerShape(14.dp), modifier = Modifier.fillMaxWidth(),
        )

        Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.SpaceBetween) {
            Text("Posisi di peta", fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
            OutlinedButton(onClick = { gunakanLokasi() }, enabled = !mencari, shape = RoundedCornerShape(12.dp)) {
                Icon(Lucide.MapPin, contentDescription = null, tint = Emerald, modifier = Modifier.size(16.dp))
                Text(if (mencari) "Mencari…" else "Gunakan lokasi saya", modifier = Modifier.padding(start = 6.dp), fontSize = 13.sp)
            }
        }

        PilihTitikMap(
            lat = lat, lng = lng,
            onPick = { la, lo -> pilih(la, lo, "Posisi dipilih dari peta. Geser penanda bila perlu.") },
            modifier = Modifier.fillMaxWidth().height(280.dp).clip(RoundedCornerShape(16.dp)),
        )
        Text(info, color = Slate500, fontSize = 12.sp)
        if (lat != null && lng != null) Text("%.6f, %.6f".format(lat, lng), color = Slate500, fontSize = 12.sp)

        error?.let {
            Text(it, color = Red, fontSize = 13.sp, modifier = Modifier.fillMaxWidth().background(Color(0xFFFEF2F2), RoundedCornerShape(12.dp)).padding(12.dp))
        }

        PrimaryButton("Ajukan titik", loading = busy) {
            error = when {
                nama.trim().length < 3 -> "Isi nama lokasi (minimal 3 karakter)."
                lat == null || lng == null -> "Tandai posisi titik di peta."
                else -> null
            }
            val la = lat
            val lo = lng
            if (error != null || la == null || lo == null) return@PrimaryButton
            busy = true
            scope.launch {
                try {
                    client.api.ajukanTitik(AjukanTitikRequest(nama.trim(), keterangan.trim().ifEmpty { null }, la, lo))
                    onDone()
                } catch (e: Exception) {
                    error = client.errorMessage(e)
                } finally {
                    busy = false
                }
            }
        }
        TextButton(onClick = onCancel, modifier = Modifier.fillMaxWidth()) { Text("Batal") }
    }
}

/** Peta OSM: ketuk untuk menandai, penanda bisa digeser. Tanpa SDK peta berbayar dan tanpa kunci API. */
@SuppressLint("ClickableViewAccessibility")
@Composable
private fun PilihTitikMap(lat: Double?, lng: Double?, onPick: (Double, Double) -> Unit, modifier: Modifier) {
    val context = LocalContext.current
    val lifecycle = LocalLifecycleOwner.current.lifecycle

    val mapView = remember {
        Configuration.getInstance().apply {
            userAgentValue = context.packageName
            osmdroidBasePath = java.io.File(context.cacheDir, "osmdroid")
            osmdroidTileCache = java.io.File(context.cacheDir, "osmdroid/tiles")
        }
        MapView(context).apply {
            setTileSource(TileSourceFactory.MAPNIK)
            setMultiTouchControls(true)
            isTilesScaledToDpi = true
            minZoomLevel = 3.0
            maxZoomLevel = 19.0
            controller.setZoom(5.0)
            controller.setCenter(GeoPoint(-2.5, 118.0))
            setOnTouchListener { v, ev ->
                when (ev.actionMasked) {
                    MotionEvent.ACTION_DOWN, MotionEvent.ACTION_MOVE -> v.parent?.requestDisallowInterceptTouchEvent(true)
                    MotionEvent.ACTION_UP, MotionEvent.ACTION_CANCEL -> v.parent?.requestDisallowInterceptTouchEvent(false)
                }
                false
            }
        }
    }
    val marker = remember(mapView) {
        Marker(mapView).apply {
            isDraggable = true
            setAnchor(Marker.ANCHOR_CENTER, Marker.ANCHOR_BOTTOM)
            setOnMarkerDragListener(object : Marker.OnMarkerDragListener {
                override fun onMarkerDrag(m: Marker) = Unit
                override fun onMarkerDragStart(m: Marker) = Unit
                override fun onMarkerDragEnd(m: Marker) {
                    onPick(m.position.latitude, m.position.longitude)
                }
            })
        }
    }
    val ketuk = remember(mapView) {
        MapEventsOverlay(object : MapEventsReceiver {
            override fun singleTapConfirmedHelper(p: GeoPoint): Boolean {
                onPick(p.latitude, p.longitude)
                return true
            }

            override fun longPressHelper(p: GeoPoint): Boolean = false
        })
    }

    DisposableEffect(lifecycle, mapView) {
        val observer = LifecycleEventObserver { _, event ->
            when (event) {
                Lifecycle.Event.ON_RESUME -> mapView.onResume()
                Lifecycle.Event.ON_PAUSE -> mapView.onPause()
                else -> Unit
            }
        }
        lifecycle.addObserver(observer)
        if (lifecycle.currentState.isAtLeast(Lifecycle.State.RESUMED)) mapView.onResume()
        onDispose {
            lifecycle.removeObserver(observer)
            mapView.onPause()
            mapView.onDetach()
        }
    }

    AndroidView(
        factory = { mapView },
        modifier = modifier,
        update = { map ->
            if (!map.overlays.contains(ketuk)) map.overlays.add(0, ketuk)
            if (lat != null && lng != null) {
                val p = GeoPoint(lat, lng)
                marker.position = p
                if (!map.overlays.contains(marker)) map.overlays.add(marker)
                if (map.zoomLevelDouble < 17.0) map.controller.setZoom(17.0)
                map.controller.animateTo(p)
            }
            map.invalidate()
        },
    )
}

/**
 * Mengambil posisi terbaik dari GPS/jaringan: mengikuti pembaruan beberapa detik dan melaporkan hasil
 * yang paling akurat (pembacaan pertama sering kasar). Berhenti saat akurasi <= 20 m atau waktu habis.
 */
private class GpsFix(private val context: Context) {
    private val manager = context.getSystemService(Context.LOCATION_SERVICE) as LocationManager
    private var listener: LocationListener? = null

    fun stop() {
        listener?.let { runCatching { manager.removeUpdates(it) } }
        listener = null
    }

    @SuppressLint("MissingPermission")
    suspend fun cari(onUpdate: (Location) -> Unit, batasMs: Long = 15_000): Boolean {
        stop()
        var terbaik: Float = Float.MAX_VALUE
        var dapat = false
        val l = object : LocationListener {
            override fun onLocationChanged(loc: Location) {
                if (loc.hasAccuracy() && loc.accuracy >= terbaik) return
                terbaik = if (loc.hasAccuracy()) loc.accuracy else terbaik
                dapat = true
                onUpdate(loc)
            }

            @Deprecated("Deprecated in Java")
            override fun onStatusChanged(provider: String?, status: Int, extras: Bundle?) = Unit
            override fun onProviderEnabled(provider: String) = Unit
            override fun onProviderDisabled(provider: String) = Unit
        }
        listener = l
        val providers = listOf(LocationManager.GPS_PROVIDER, LocationManager.NETWORK_PROVIDER).filter { runCatching { manager.isProviderEnabled(it) }.getOrDefault(false) }
        if (providers.isEmpty()) return false
        providers.forEach { p -> runCatching { manager.requestLocationUpdates(p, 1000L, 0f, l, android.os.Looper.getMainLooper()) } }

        var waktu = 0L
        while (waktu < batasMs && terbaik > 20f) {
            delay(500)
            waktu += 500
        }
        stop()
        return dapat
    }
}
