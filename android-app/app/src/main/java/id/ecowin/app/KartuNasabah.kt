package id.ecowin.app

import android.app.Activity
import android.content.Context
import android.content.ContextWrapper
import android.content.pm.ActivityInfo
import android.graphics.BitmapFactory
import android.util.Base64
import android.view.WindowManager
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.BoxWithConstraints
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.aspectRatio
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.offset
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.safeDrawingPadding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Icon
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.FilterQuality
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

private const val RASIO_KARTU = 1.586f // kartu bank standar 85,6 x 54 mm

private tailrec fun Context.cariActivity(): Activity? = when (this) {
    is Activity -> this
    is ContextWrapper -> baseContext.cariActivity()
    else -> null
}

/**
 * Selama halaman kartu terbuka: layar diputar melintang (otomatis, tanpa menunggu HP diputar), kecerahan dinaikkan supaya QR
 * mudah dipindai, dan layar tetap menyala. Semuanya dikembalikan persis seperti semula begitu halaman ditutup.
 */
@Composable
private fun LayarUntukKartu() {
    val konteks = LocalContext.current
    DisposableEffect(Unit) {
        val activity = konteks.cariActivity()
        val window = activity?.window
        val orientasiAwal = activity?.requestedOrientation ?: ActivityInfo.SCREEN_ORIENTATION_UNSPECIFIED
        val kecerahanAwal = window?.attributes?.screenBrightness ?: WindowManager.LayoutParams.BRIGHTNESS_OVERRIDE_NONE

        activity?.requestedOrientation = ActivityInfo.SCREEN_ORIENTATION_SENSOR_LANDSCAPE
        window?.let { w -> w.attributes = w.attributes.also { it.screenBrightness = 1f } }
        window?.addFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON)

        onDispose {
            activity?.requestedOrientation = orientasiAwal
            window?.let { w -> w.attributes = w.attributes.also { it.screenBrightness = kecerahanAwal } }
            window?.clearFlags(WindowManager.LayoutParams.FLAG_KEEP_SCREEN_ON)
        }
    }
}

/** Halaman Kartu Nasabah layar penuh: kartu melintang berisi QR besar untuk dipindai petugas saat setor sampah. */
@Composable
fun KartuNasabahScreen(client: ApiClient, user: UserDto, onTutup: () -> Unit) {
    LayarUntukKartu()
    val qr = rememberLoadable(client) { client.api.qr() }

    Box(Modifier.fillMaxSize().background(Brush.verticalGradient(listOf(EmeraldSoft, Canvas)))) {
        BoxWithConstraints(Modifier.fillMaxSize().safeDrawingPadding().padding(horizontal = 24.dp, vertical = 12.dp), contentAlignment = Alignment.Center) {
            LoadableContent(qr) { q ->
                // Kartu mengisi ruang yang tersedia dengan rasio kartu asli; ruang kecil di bawah untuk petunjuk.
                val lebarKartu = minOf(maxWidth, (maxHeight - 30.dp) * RASIO_KARTU)
                Column(horizontalAlignment = Alignment.CenterHorizontally, verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    KartuHorizontal(q, user, Modifier.width(lebarKartu).aspectRatio(RASIO_KARTU))
                    Text(
                        "Tunjukkan kartu ini kepada petugas. Kecerahan layar dinaikkan agar mudah dipindai.",
                        color = Slate500, fontSize = 12.sp, textAlign = TextAlign.Center, maxLines = 1, overflow = TextOverflow.Ellipsis,
                    )
                }
            }
        }

        Box(
            Modifier.align(Alignment.TopStart).safeDrawingPadding().padding(12.dp).size(44.dp)
                .background(Color.White, CircleShape).border(1.dp, Slate200, CircleShape)
                .clickable(role = androidx.compose.ui.semantics.Role.Button, onClickLabel = "Tutup kartu", onClick = onTutup),
            contentAlignment = Alignment.Center,
        ) { Icon(Lucide.X, contentDescription = "Tutup kartu", tint = Slate900, modifier = Modifier.size(20.dp)) }
    }
}

/** Kartu melintang: merek dan data nasabah di kiri, QR putih besar di kanan. Ukuran isi mengikuti tinggi kartu. */
@Composable
private fun KartuHorizontal(q: QrDto, user: UserDto, modifier: Modifier = Modifier) {
    val bitmap = remember(q.qrPngBase64) {
        runCatching {
            val bytes = Base64.decode(q.qrPngBase64, Base64.DEFAULT)
            BitmapFactory.decodeByteArray(bytes, 0, bytes.size)?.asImageBitmap()
        }.getOrNull()
    }
    val bentuk = RoundedCornerShape(28.dp)

    BoxWithConstraints(
        modifier
            .shadow(18.dp, bentuk, ambientColor = Color(0x33065F46), spotColor = Color(0x80065F46))
            .clip(bentuk)
            .background(Brush.linearGradient(listOf(Color(0xFF064E3B), Color(0xFF059669), Color(0xFF34D399)), start = Offset(0f, 0f), end = Offset(1800f, 1100f))),
    ) {
        val tinggi: Dp = maxHeight
        val skala = (tinggi.value / 300f).coerceIn(0.65f, 1.3f)
        val pad = tinggi * 0.085f
        val sisiQr = tinggi * 0.66f

        // Hiasan: lingkaran bening dan daun besar yang samar.
        Box(Modifier.align(Alignment.TopEnd).offset(x = tinggi * 0.25f, y = -tinggi * 0.35f).size(tinggi * 0.95f).background(Color.White.copy(alpha = 0.09f), CircleShape))
        Box(Modifier.align(Alignment.BottomStart).offset(x = -tinggi * 0.2f, y = tinggi * 0.3f).size(tinggi * 0.7f).background(Color.White.copy(alpha = 0.07f), CircleShape))
        Icon(
            Lucide.Leaf, contentDescription = null, tint = Color.White.copy(alpha = 0.07f),
            modifier = Modifier.align(Alignment.BottomEnd).offset(x = -tinggi * 0.02f, y = tinggi * 0.06f).size(tinggi * 0.9f),
        )
        Box(Modifier.matchParentSize().border(1.dp, Color.White.copy(alpha = 0.28f), bentuk))

        Row(Modifier.fillMaxSize().padding(pad), horizontalArrangement = Arrangement.spacedBy(pad)) {
            Column(Modifier.weight(1f).fillMaxHeight(), verticalArrangement = Arrangement.SpaceBetween) {
                // Merek
                Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    androidx.compose.foundation.Image(
                        painterResource(R.drawable.ecowin_emblem), contentDescription = null,
                        modifier = Modifier.size(tinggi * 0.15f).background(Color.White, RoundedCornerShape(10.dp)).padding(4.dp),
                    )
                    Column {
                        Row {
                            Text("Eco", color = Color.White, fontSize = (17 * skala).sp, fontWeight = FontWeight.ExtraBold)
                            Text("Win", color = Color(0xFFA7F3D0), fontSize = (17 * skala).sp, fontWeight = FontWeight.ExtraBold)
                        }
                        Text("BANK SAMPAH DIGITAL", color = Color(0xFFA7F3D0), fontSize = (8.5f * skala).sp, fontWeight = FontWeight.Bold, letterSpacing = 1.sp)
                    }
                }

                // Identitas nasabah
                Column(verticalArrangement = Arrangement.spacedBy(3.dp)) {
                    Text("KARTU NASABAH", color = Color(0xFFA7F3D0), fontSize = (10 * skala).sp, fontWeight = FontWeight.Bold, letterSpacing = 1.5.sp)
                    Text(
                        q.nama ?: user.nama ?: "", color = Color.White, fontSize = (24 * skala).sp, lineHeight = (28 * skala).sp,
                        fontWeight = FontWeight.ExtraBold, maxLines = 2, overflow = TextOverflow.Ellipsis,
                    )
                    Text(
                        q.nomorNasabah ?: "", color = Color(0xFFD1FAE5), fontFamily = FontFamily.Monospace,
                        fontSize = (15 * skala).sp, letterSpacing = 2.sp, fontWeight = FontWeight.SemiBold,
                    )
                }

                // Bank sampah
                val bank = user.nasabah?.bankSampah
                val lokasi = listOfNotNull(bank?.nama, bank?.rt?.let { rt -> "RT $rt" + (bank.rw?.let { "/RW $it" } ?: "") }).joinToString(" · ")
                Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                    Icon(Lucide.MapPin, contentDescription = null, tint = Color(0xFFA7F3D0), modifier = Modifier.size((14 * skala).dp))
                    Text(lokasi.ifBlank { "EcoWin" }, color = Color.White.copy(alpha = 0.92f), fontSize = (12 * skala).sp, fontWeight = FontWeight.Medium, maxLines = 1, overflow = TextOverflow.Ellipsis)
                }
            }

            Column(Modifier.fillMaxHeight(), horizontalAlignment = Alignment.CenterHorizontally, verticalArrangement = Arrangement.Center) {
                Box(Modifier.size(sisiQr).background(Color.White, RoundedCornerShape(18.dp)).padding(sisiQr * 0.04f), contentAlignment = Alignment.Center) {
                    bitmap?.let {
                        Image(it, contentDescription = "QR Card nasabah", filterQuality = FilterQuality.None, modifier = Modifier.fillMaxSize())
                    }
                }
                Text("PINDAI OLEH PETUGAS", color = Color(0xFFD1FAE5), fontSize = (8.5f * skala).sp, fontWeight = FontWeight.Bold, letterSpacing = 1.sp, modifier = Modifier.padding(top = 6.dp))
            }
        }
    }
}
