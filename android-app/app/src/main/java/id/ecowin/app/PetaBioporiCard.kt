package id.ecowin.app

import android.annotation.SuppressLint
import android.graphics.Color as AColor
import android.view.MotionEvent
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.LifecycleEventObserver
import androidx.lifecycle.compose.LocalLifecycleOwner
import org.osmdroid.config.Configuration
import org.osmdroid.tileprovider.tilesource.TileSourceFactory
import org.osmdroid.util.BoundingBox
import org.osmdroid.util.GeoPoint
import org.osmdroid.views.MapView
import org.osmdroid.events.MapEventsReceiver
import org.osmdroid.views.overlay.MapEventsOverlay
import org.osmdroid.views.overlay.Marker
import org.osmdroid.views.overlay.infowindow.InfoWindow

/** Koordinat valid (sama dengan aturan di Web: angka terbatas dan dalam rentang lintang/bujur). */
private fun TitikBioporiDto.punyaKoordinat(): Boolean {
    val la = latitude ?: return false
    val lo = longitude ?: return false
    return la.isFinite() && lo.isFinite() && kotlin.math.abs(la) <= 90 && kotlin.math.abs(lo) <= 180
}

/**
 * Peta lokasi Biopori di dalam aplikasi (OpenStreetMap lewat osmdroid; tanpa kunci API dan tanpa izin lokasi).
 * Sama dengan Web: filter Semua / BioporiPrint, penanda hijau untuk BioporiPrint, ketuk penanda untuk detail.
 */
@Composable
fun PetaBioporiCard(titik: List<TitikBioporiDto>) {
    var hanyaPrint by remember { mutableStateOf(false) }
    val jumlahPrint = titik.count { it.bioporiprint }
    val tampil = (if (hanyaPrint) titik.filter { it.bioporiprint } else titik)
    val dipeta = tampil.filter { it.punyaKoordinat() }
    val tanpaKoordinat = tampil.filterNot { it.punyaKoordinat() }

    EcoCard {
        Text("Peta lokasi Biopori", fontWeight = FontWeight.Bold)
        Text("Titik Biopori di Bank Sampah Anda. Ketuk penanda untuk detail.", color = Slate500, fontSize = 12.sp)
        Row(horizontalArrangement = Arrangement.spacedBy(8.dp), modifier = Modifier.fillMaxWidth().padding(top = 4.dp)) {
            FilterPill("Semua (${titik.size})", !hanyaPrint) { hanyaPrint = false }
            FilterPill("BioporiPrint ($jumlahPrint)", hanyaPrint) { hanyaPrint = true }
        }

        if (dipeta.isEmpty()) {
            EmptyText(if (hanyaPrint) "Belum ada titik BioporiPrint yang berkoordinat di Bank Sampah Anda." else "Belum ada titik Biopori berkoordinat di Bank Sampah Anda.")
        } else {
            OsmMap(dipeta, Modifier.fillMaxWidth().height(300.dp).clip(RoundedCornerShape(16.dp)))
        }
        if (tanpaKoordinat.isNotEmpty()) {
            Text(
                "${tanpaKoordinat.size} titik belum memiliki koordinat: " + tanpaKoordinat.joinToString(", ") { it.namaLokasi ?: "Lokasi #${it.id}" } + ".",
                color = Slate500, fontSize = 12.sp,
            )
        }
    }
}

@Composable
private fun FilterPill(label: String, aktif: Boolean, onClick: () -> Unit) {
    Text(
        label,
        color = if (aktif) androidx.compose.ui.graphics.Color.White else Slate500,
        fontWeight = FontWeight.SemiBold,
        fontSize = 12.sp,
        modifier = Modifier
            .clip(RoundedCornerShape(50))
            .background(if (aktif) Emerald else EmeraldSoft)
            .clickable(onClick = onClick)
            .padding(horizontal = 14.dp, vertical = 8.dp),
    )
}

@SuppressLint("ClickableViewAccessibility")
@Composable
private fun OsmMap(titik: List<TitikBioporiDto>, modifier: Modifier) {
    val context = LocalContext.current
    val lifecycle = LocalLifecycleOwner.current.lifecycle

    val mapView = remember {
        // Pengaturan osmdroid: identitas aplikasi untuk server tile dan cache di folder cache aplikasi (tanpa izin storage).
        Configuration.getInstance().apply {
            userAgentValue = context.packageName
            osmdroidBasePath = java.io.File(context.cacheDir, "osmdroid")
            osmdroidTileCache = java.io.File(context.cacheDir, "osmdroid/tiles")
        }
        MapView(context).apply {
            setTileSource(TileSourceFactory.MAPNIK)
            setMultiTouchControls(true)
            // Tombol zoom bawaan berupa kotak putih besar; cukup cubit/ketuk dua kali untuk zoom.
            zoomController.setVisibility(org.osmdroid.views.CustomZoomButtonsController.Visibility.NEVER)
            isTilesScaledToDpi = true
            minZoomLevel = 3.0
            maxZoomLevel = 19.0
            // Peta berada di dalam daftar yang bisa digulir: sentuhan pada peta tidak boleh dicuri oleh daftar.
            setOnTouchListener { v, ev ->
                when (ev.actionMasked) {
                    MotionEvent.ACTION_DOWN, MotionEvent.ACTION_MOVE -> v.parent?.requestDisallowInterceptTouchEvent(true)
                    MotionEvent.ACTION_UP, MotionEvent.ACTION_CANCEL -> v.parent?.requestDisallowInterceptTouchEvent(false)
                }
                false
            }
        }
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
            map.overlays.clear()
            // Ketuk area peta kosong menutup gelembung info yang terbuka (lapisan ini paling bawah, jadi penanda tetap diprioritaskan).
            map.overlays.add(MapEventsOverlay(object : MapEventsReceiver {
                override fun singleTapConfirmedHelper(p: GeoPoint?): Boolean { InfoWindow.closeAllInfoWindowsOn(map); return false }
                override fun longPressHelper(p: GeoPoint?): Boolean = false
            }))
            val titikGeo = titik.map { GeoPoint(it.latitude!!, it.longitude!!) }
            titik.forEachIndexed { i, t ->
                Marker(map).apply {
                    position = titikGeo[i]
                    setAnchor(Marker.ANCHOR_CENTER, Marker.ANCHOR_CENTER)
                    icon = lingkaran(t.bioporiprint)
                    infoWindow = InfoWindowTitik(map)
                    // Hanya satu gelembung terbuka: ketuk penanda lain menutup yang sebelumnya.
                    setOnMarkerClickListener { m, mv ->
                        InfoWindow.closeAllInfoWindowsOn(mv)
                        m.showInfoWindow()
                        mv.controller.animateTo(m.position)
                        true
                    }
                    title = t.namaLokasi ?: "Lokasi #${t.id}"
                    snippet = buildString {
                        append(statusLabel(t.statusPanen))
                        if (t.bioporiprint) append(" · BioporiPrint")
                        append("\nEstimasi panen: ").append(tanggal(t.estimasiPanenAt, withTime = false))
                    }
                    map.overlays.add(this)
                }
            }
            map.post {
                if (titikGeo.size == 1) {
                    map.controller.setZoom(17.0)
                    map.controller.setCenter(titikGeo[0])
                } else {
                    map.zoomToBoundingBox(BoundingBox.fromGeoPointsSafe(titikGeo), false, 80)
                    if (map.zoomLevelDouble > 17.0) map.controller.setZoom(17.0)
                }
                // Gambar ulang setelah zoom/pusat berubah; tanpa ini peta baru tampil setelah disentuh.
                map.invalidate()
                map.postDelayed({ map.invalidate() }, 400)
            }
            map.invalidate()
        },
    )
}

/** Penanda bulat seperti Web: hijau + ikon printer untuk BioporiPrint, putih + pin hijau untuk titik biasa. */
private fun lingkaran(print: Boolean): android.graphics.drawable.Drawable {
    val ukuran = 96
    val bmp = android.graphics.Bitmap.createBitmap(ukuran, ukuran, android.graphics.Bitmap.Config.ARGB_8888)
    val c = android.graphics.Canvas(bmp)
    val p = android.graphics.Paint(android.graphics.Paint.ANTI_ALIAS_FLAG)
    val hijau = AColor.parseColor("#059669")
    val r = ukuran / 2f
    p.color = AColor.argb(60, 15, 23, 42); c.drawCircle(r, r + 2f, r - 4f, p)
    p.color = AColor.WHITE; c.drawCircle(r, r, r - 4f, p)
    p.color = if (print) hijau else AColor.WHITE; c.drawCircle(r, r, r - 10f, p)
    // Jalur ikon Lucide yang sama dengan Web (printer / pin).
    val jalur = if (print) listOf(
        "M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2",
        "M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6",
        "M7 14h10a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-6a1 1 0 0 1 1-1z",
    ) else listOf(
        "M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0",
        "M9 10a3 3 0 1 0 6 0a3 3 0 1 0 -6 0",
    )
    p.style = android.graphics.Paint.Style.STROKE
    p.strokeWidth = 2f
    p.strokeCap = android.graphics.Paint.Cap.ROUND
    p.strokeJoin = android.graphics.Paint.Join.ROUND
    p.color = if (print) AColor.WHITE else hijau
    c.save()
    c.translate(r - 20f, r - 20f)
    c.scale(40f / 24f, 40f / 24f)
    jalur.forEach { d -> c.drawPath(androidx.core.graphics.PathParser.createPathFromPathData(d), p) }
    c.restore()
    return android.graphics.drawable.BitmapDrawable(android.content.res.Resources.getSystem(), bmp)
}
