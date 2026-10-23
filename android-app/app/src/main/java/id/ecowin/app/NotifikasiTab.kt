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
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.IconButton
import androidx.compose.material3.TextButton
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
    var konfirmasi by remember { mutableStateOf<String?>(null) } // "dibaca" | "semua"
    val scope = rememberCoroutineScope()
    val data = rememberLoadable(client, refreshKey, lokal) { client.api.notifikasi() }

    TabList {
        item {
            Text(
                "Atur pemberitahuan HP dan nada", color = Emerald, fontWeight = FontWeight.SemiBold, fontSize = 13.sp,
                modifier = Modifier.background(EmeraldSoft, RoundedCornerShape(50)).clickable { go(Tab.Pengaturan) }.padding(horizontal = 14.dp, vertical = 8.dp),
            )
        }
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
                    if (respons.data.isNotEmpty()) {
                        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            if (respons.data.any { it.dibaca }) {
                                Text(
                                    "Hapus yang sudah dibaca", color = Slate900, fontWeight = FontWeight.SemiBold, fontSize = 12.sp,
                                    modifier = Modifier.border(1.dp, Slate200, RoundedCornerShape(50)).clickable { konfirmasi = "dibaca" }.padding(horizontal = 12.dp, vertical = 7.dp),
                                )
                            }
                            Text(
                                "Hapus semua", color = Red, fontWeight = FontWeight.SemiBold, fontSize = 12.sp,
                                modifier = Modifier.clickable { konfirmasi = "semua" }.padding(horizontal = 12.dp, vertical = 7.dp),
                            )
                        }
                    }
                    konfirmasi?.let { jenis ->
                        AlertDialog(
                            onDismissRequest = { konfirmasi = null },
                            title = { Text(if (jenis == "semua") "Hapus semua notifikasi?" else "Hapus yang sudah dibaca?") },
                            text = { Text(if (jenis == "semua") "Semua notifikasi Anda akan dihapus dan tidak bisa dikembalikan." else "Notifikasi yang sudah dibaca akan dihapus. Yang belum dibaca tetap ada.") },
                            confirmButton = {
                                TextButton(onClick = {
                                    konfirmasi = null
                                    scope.launch {
                                        runCatching { client.api.hapusSemuaNotif(if (jenis == "dibaca") 1 else null) }
                                        lokal++
                                        onBerubah()
                                    }
                                }) { Text("Ya, hapus", color = Red, fontWeight = FontWeight.Bold) }
                            },
                            dismissButton = { TextButton(onClick = { konfirmasi = null }) { Text("Batal") } },
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
                            IconButton(
                                onClick = {
                                    scope.launch {
                                        runCatching { client.api.hapusNotif(n.id) }
                                        lokal++
                                        onBerubah()
                                    }
                                },
                                modifier = Modifier.size(40.dp),
                            ) { Icon(Lucide.Trash2, contentDescription = "Hapus notifikasi: ${n.judul}", tint = Slate500, modifier = Modifier.size(18.dp)) }
                        }
                    }
                    if (respons.data.isNotEmpty()) {
                        Text("Notifikasi dihapus otomatis setelah 30 hari.", color = Slate500, fontSize = 11.sp, modifier = Modifier.fillMaxWidth().padding(top = 4.dp), textAlign = androidx.compose.ui.text.style.TextAlign.Center)
                    }
                }
            }
        }
    }
}
