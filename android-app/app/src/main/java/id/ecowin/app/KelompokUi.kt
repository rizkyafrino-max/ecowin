package id.ecowin.app

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ColumnScope
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Icon
import androidx.compose.material3.Switch
import androidx.compose.material3.SwitchDefaults
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

/*
 * Komponen daftar berkelompok yang dipakai Profil dan Pengaturan agar tampilannya seragam: satu kartu putih per kelompok,
 * berisi baris-baris dengan ikon berlatar lembut, judul, keterangan, dan kontrol di kanan.
 */

/** Situs pembuat aplikasi, dibuka di peramban. */
internal const val URL_PEMBUAT = "https://rizky.pplgku.my.id/"

internal val LatarNetral = Color(0xFFF1F5F9)
internal val Slate400 = Color(0xFF94A3B8)

/** Satu kelompok: judul kecil, kartu putih berisi baris-baris, lalu catatan opsional di bawahnya. */
@Composable
internal fun Kelompok(judul: String, catatan: String? = null, isi: @Composable ColumnScope.() -> Unit) {
    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
        Text(judul.uppercase(), color = Slate500, fontSize = 12.sp, fontWeight = FontWeight.Bold, letterSpacing = 1.sp, modifier = Modifier.padding(start = 6.dp))
        Column(
            Modifier.fillMaxWidth()
                .clip(RoundedCornerShape(20.dp))
                .background(Color.White)
                .border(1.dp, Slate200, RoundedCornerShape(20.dp)),
            content = isi,
        )
        catatan?.let { Text(it, color = Slate500, fontSize = 12.sp, lineHeight = 17.sp, modifier = Modifier.padding(horizontal = 6.dp)) }
    }
}

/** Baris: ikon berlatar lembut, judul dan keterangan, lalu kontrol di kanan. Bisa diketuk seluruh barisnya. */
@Composable
internal fun Baris(
    ikon: ImageVector,
    judul: String,
    keterangan: String? = null,
    warnaIkon: Color = Emerald,
    latarIkon: Color = EmeraldSoft,
    dipilih: Boolean = false,
    peran: Role = Role.Button,
    onClick: (() -> Unit)? = null,
    kanan: @Composable () -> Unit = {},
) {
    Row(
        Modifier.fillMaxWidth()
            .background(if (dipilih) EmeraldSoft else Color.Transparent)
            .then(if (onClick != null) Modifier.clickable(role = peran, onClick = onClick) else Modifier)
            .padding(horizontal = 16.dp, vertical = 14.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(14.dp),
    ) {
        Box(Modifier.size(40.dp).background(latarIkon, RoundedCornerShape(12.dp)), contentAlignment = Alignment.Center) {
            Icon(ikon, contentDescription = null, tint = warnaIkon, modifier = Modifier.size(20.dp))
        }
        Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(2.dp)) {
            Text(judul, fontWeight = FontWeight.SemiBold, fontSize = 15.sp, color = Slate900)
            keterangan?.let { Text(it, color = Slate500, fontSize = 12.5.sp, lineHeight = 17.sp) }
        }
        kanan()
    }
}

/** Saklar tanpa penanganan sendiri: seluruh baris yang menanganinya, supaya area ketuk besar dan terbaca layanan aksesibilitas. */
@Composable
internal fun Saklar(nyala: Boolean) {
    Switch(checked = nyala, onCheckedChange = null, colors = SwitchDefaults.colors(checkedTrackColor = Emerald))
}

@Composable
internal fun Pemisah() {
    Box(Modifier.fillMaxWidth().padding(start = 70.dp).height(1.dp).background(LatarNetral))
}

/** Panah kecil di ujung kanan baris yang membuka layar lain. */
@Composable
internal fun PanahBaris() {
    Icon(Lucide.ChevronRight, contentDescription = null, tint = Slate400, modifier = Modifier.size(20.dp))
}

/** Ikon tautan keluar di ujung kanan baris yang membuka peramban. */
@Composable
internal fun TautanLuar() {
    Icon(Lucide.ExternalLink, contentDescription = "Dibuka di peramban", tint = Slate400, modifier = Modifier.size(18.dp))
}
