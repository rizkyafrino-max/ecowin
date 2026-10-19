package id.ecowin.app

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Icon
import androidx.compose.material3.Switch
import androidx.compose.material3.SwitchDefaults
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.launch

/** Ikon dan warna per jenis notifikasi. Teks selalu ada, jadi makna tidak bergantung pada warna saja. */
private fun gayaNotif(tipe: String): Triple<ImageVector, Color, Color> = when {
    tipe == "pengumuman" -> Triple(Lucide.Megaphone, Color(0xFFB45309), Color(0xFFFEF3C7))
    tipe.endsWith("ditolak") -> Triple(Lucide.TriangleAlert, Red, RedSoft)
    tipe.startsWith("penarikan") -> Triple(Lucide.ArrowUpRight, Color(0xFF1D4ED8), Color(0xFFEFF6FF))
    tipe == "setoran_organik" || tipe.startsWith("biopori") -> Triple(Lucide.Leaf, EmeraldDeep, EmeraldSoft)
    tipe.startsWith("setoran") -> Triple(Lucide.ArrowDownLeft, EmeraldDeep, EmeraldSoft)
    tipe.startsWith("titik") -> Triple(Lucide.MapPin, EmeraldDeep, EmeraldSoft)
    tipe.startsWith("akun") -> Triple(Lucide.ShieldCheck, EmeraldDeep, EmeraldSoft)
    else -> Triple(Lucide.Bell, Slate500, Color(0xFFF1F5F9))
}

private fun tujuanNotif(aksi: String?): Tab? = when (aksi) {
    "saldo" -> Tab.Saldo
    "transaksi" -> Tab.Transaksi
    "organik" -> Tab.Organik
    "beranda" -> Tab.Beranda
    else -> null
}

/**
 * Notifikasi dalam aplikasi (sama dengan halaman Notifikasi di Web): ketuk untuk membuka dan menandai dibaca,
 * "Tandai dibaca" membaca semuanya.
 */
@Composable
fun NotifikasiTab(client: ApiClient, refreshKey: Int, onBerubah: () -> Unit, go: (Tab) -> Unit) {
    var lokal by remember { mutableIntStateOf(0) }
    val scope = rememberCoroutineScope()
    val data = rememberLoadable(client, refreshKey, lokal) { client.api.notifikasi() }

    TabList {
        item { PengaturanPemberitahuan() }
        item {
            LoadableContent(data) { respons ->
                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    if (respons.belumDibaca > 0) {
                        Text(
                            "Tandai semua dibaca", color = Emerald, fontWeight = FontWeight.SemiBold, fontSize = 13.sp,
                            modifier = Modifier
                                .background(EmeraldSoft, RoundedCornerShape(50))
                                .clickable {
                                    scope.launch {
                                        runCatching { client.api.bacaSemuaNotif() }
                                        lokal++
                                        onBerubah()
                                    }
                                }
                                .padding(horizontal = 14.dp, vertical = 8.dp),
                        )
                    }
                    if (respons.data.isEmpty()) {
                        ListCard { EmptyText("Belum ada notifikasi. Kabar penting seperti penarikan disetujui atau setoran tercatat akan muncul di sini.") }
                    }
                    respons.data.forEach { n ->
                        val (ikon, warna, latar) = gayaNotif(n.tipe)
                        Row(
                            Modifier.fillMaxWidth()
                                .background(Color.White, RoundedCornerShape(22.dp))
                                .border(BorderStroke(if (n.dibaca) 1.dp else 1.5.dp, if (n.dibaca) Slate200 else Emerald), RoundedCornerShape(22.dp))
                                .clickable {
                                    scope.launch {
                                        if (!n.dibaca) runCatching { client.api.bacaNotif(n.id) }
                                        lokal++
                                        onBerubah()
                                        tujuanNotif(n.aksi)?.let(go)
                                    }
                                }
                                .padding(14.dp),
                            horizontalArrangement = Arrangement.spacedBy(12.dp),
                        ) {
                            Box(Modifier.size(44.dp).background(latar, RoundedCornerShape(14.dp)), contentAlignment = Alignment.Center) {
                                Icon(ikon, contentDescription = null, tint = warna, modifier = Modifier.size(22.dp))
                            }
                            Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(2.dp)) {
                                Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                    Text(n.judul, fontWeight = if (n.dibaca) FontWeight.SemiBold else FontWeight.ExtraBold, fontSize = 14.sp, color = Slate900, modifier = Modifier.weight(1f, fill = false))
                                    if (!n.dibaca) Text("Baru", color = Color.White, fontSize = 10.sp, fontWeight = FontWeight.Bold, modifier = Modifier.background(Emerald, RoundedCornerShape(50)).padding(horizontal = 8.dp, vertical = 2.dp))
                                }
                                Text(n.isi, color = Slate500, fontSize = 13.sp)
                                Text(tanggal(n.createdAt, withTime = true), color = Slate500, fontSize = 11.sp)
                            }
                        }
                    }
                }
            }
        }
    }
}

/**
 * Pemberitahuan di HP: mati secara bawaan dan hanya menyala atas pilihan nasabah. Isi pesan disembunyikan di layar kunci.
 * Izin pemberitahuan Android 13+ baru diminta saat saklar dinyalakan, bukan saat aplikasi dibuka.
 */
@Composable
private fun PengaturanPemberitahuan() {
    val konteks = androidx.compose.ui.platform.LocalContext.current
    var aktif by remember { mutableStateOf(NotifikasiHp.aktif(konteks) && NotifikasiHp.izinDiberikan(konteks)) }
    var ditolak by remember { mutableStateOf(false) }
    val minta = androidx.activity.compose.rememberLauncherForActivityResult(androidx.activity.result.contract.ActivityResultContracts.RequestPermission()) { ok ->
        if (ok) { NotifikasiHp.nyalakan(konteks); aktif = true; ditolak = false } else ditolak = true
    }
    EcoCard {
        Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(12.dp)) {
            Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(2.dp)) {
                Text("Pemberitahuan di HP", fontWeight = FontWeight.Bold, fontSize = 14.sp, color = Slate900)
                Text(
                    "Dapat kabar setoran dan penarikan walau aplikasi tertutup (dicek tiap ±15 menit). Di layar kunci isi pesan disembunyikan; nominal baru terlihat setelah HP dibuka. Mati otomatis saat Anda keluar akun.",
                    color = Slate500, fontSize = 12.sp,
                )
                if (aktif) {
                    var nada by remember { mutableStateOf(NotifikasiHp.nada(konteks)) }
                    Text("Nada notifikasi", fontWeight = FontWeight.SemiBold, fontSize = 12.sp, color = Slate900, modifier = Modifier.padding(top = 6.dp))
                    Row(horizontalArrangement = Arrangement.spacedBy(6.dp), modifier = Modifier.fillMaxWidth()) {
                        NotifikasiHp.Nada.entries.forEach { n ->
                            val dipilih = n == nada
                            Text(
                                n.label, fontSize = 12.sp, fontWeight = FontWeight.SemiBold,
                                color = if (dipilih) Color.White else Slate500,
                                modifier = Modifier
                                    .background(if (dipilih) Emerald else EmeraldSoft, RoundedCornerShape(50))
                                    .clickable { nada = n; NotifikasiHp.setNada(konteks, n); NotifikasiHp.pratinjau(konteks, n) }
                                    .padding(horizontal = 12.dp, vertical = 7.dp),
                            )
                        }
                    }
                    Text("Ketuk untuk memilih dan mendengar contohnya. Bisa juga diubah di Pengaturan HP > Aplikasi > EcoWin > Notifikasi.", color = Slate500, fontSize = 11.sp)
                }
                if (ditolak) Text("Izin pemberitahuan ditolak. Aktifkan lewat Pengaturan HP > Aplikasi > EcoWin > Notifikasi.", color = Red, fontSize = 12.sp)
            }
            Switch(
                checked = aktif,
                onCheckedChange = { nyala ->
                    if (!nyala) { NotifikasiHp.berhenti(konteks); aktif = false }
                    else if (NotifikasiHp.izinDiberikan(konteks)) { NotifikasiHp.nyalakan(konteks); aktif = true; ditolak = false }
                    else minta.launch(android.Manifest.permission.POST_NOTIFICATIONS)
                },
                colors = SwitchDefaults.colors(checkedTrackColor = Emerald),
            )
        }
    }
}
