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
import androidx.compose.foundation.selection.selectable
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Switch
import androidx.compose.material3.SwitchDefaults
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

private fun deskripsi(n: NotifikasiHp.Nada) = when (n) {
    NotifikasiHp.Nada.Ecowin -> "Tiga denting naik, nada khas EcoWin"
    NotifikasiHp.Nada.Daun -> "Dua nada kayu yang lembut"
    NotifikasiHp.Nada.Koin -> "Dua \"ting\" cepat yang ceria"
    NotifikasiHp.Nada.Sistem -> "Nada notifikasi bawaan HP Anda"
}

/**
 * Halaman Pengaturan: pengaturan perangkat dan pemberitahuan dikumpulkan di sini agar layar lain tetap rapi.
 *
 * Pemberitahuan HP mati secara bawaan dan hanya menyala atas pilihan nasabah. Isi pesan disembunyikan di layar kunci.
 * Izin pemberitahuan Android 13+ baru diminta saat saklar dinyalakan, bukan saat aplikasi dibuka.
 */
@Composable
fun PengaturanTab() {
    val konteks = LocalContext.current
    var aktif by remember { mutableStateOf(NotifikasiHp.aktif(konteks) && NotifikasiHp.izinDiberikan(konteks)) }
    var ditolak by remember { mutableStateOf(false) }
    var nada by remember { mutableStateOf(NotifikasiHp.nada(konteks)) }
    val minta = androidx.activity.compose.rememberLauncherForActivityResult(androidx.activity.result.contract.ActivityResultContracts.RequestPermission()) { ok ->
        if (ok) { NotifikasiHp.nyalakan(konteks); aktif = true; ditolak = false } else ditolak = true
    }

    TabList {
        item { Text("PEMBERITAHUAN", color = Slate500, fontSize = 12.sp, fontWeight = FontWeight.Bold, letterSpacing = 1.sp) }

        item {
            EcoCard {
                Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                    Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                        Text("Pemberitahuan di HP", fontWeight = FontWeight.Bold, fontSize = 15.sp, color = Slate900)
                        Text("Dapat kabar setoran dan penarikan walau aplikasi tertutup.", color = Slate500, fontSize = 13.sp)
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
                if (ditolak) Text("Izin pemberitahuan ditolak. Aktifkan lewat Pengaturan HP > Aplikasi > EcoWin > Notifikasi.", color = Red, fontSize = 12.sp, modifier = Modifier.padding(top = 8.dp))
                Column(Modifier.padding(top = 12.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                    Catatan("Dicek tiap ±15 menit saat ada koneksi internet.")
                    Catatan("Di layar kunci isi pesan disembunyikan; nominal baru terlihat setelah HP dibuka.")
                    Catatan("Mati otomatis saat Anda keluar akun.")
                }
            }
        }

        if (aktif) {
            item { Text("NADA NOTIFIKASI", color = Slate500, fontSize = 12.sp, fontWeight = FontWeight.Bold, letterSpacing = 1.sp, modifier = Modifier.padding(top = 4.dp)) }
            item {
                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                    NotifikasiHp.Nada.entries.forEach { n ->
                        val dipilih = n == nada
                        Row(
                            Modifier.fillMaxWidth()
                                .background(Color.White, RoundedCornerShape(18.dp))
                                .border(BorderStroke(if (dipilih) 2.dp else 1.dp, if (dipilih) Emerald else Slate200), RoundedCornerShape(18.dp))
                                .selectable(selected = dipilih, role = Role.RadioButton) {
                                    nada = n
                                    NotifikasiHp.setNada(konteks, n)
                                    NotifikasiHp.pratinjau(konteks, n)
                                }
                                .padding(horizontal = 16.dp, vertical = 14.dp),
                            horizontalArrangement = Arrangement.spacedBy(14.dp),
                            verticalAlignment = Alignment.CenterVertically,
                        ) {
                            Box(
                                Modifier.size(22.dp).border(2.dp, if (dipilih) Emerald else Slate200, CircleShape),
                                contentAlignment = Alignment.Center,
                            ) { if (dipilih) Box(Modifier.size(12.dp).background(Emerald, CircleShape)) }
                            Column(Modifier.weight(1f)) {
                                Text(n.label, fontWeight = FontWeight.SemiBold, fontSize = 14.sp, color = Slate900)
                                Text(deskripsi(n), color = Slate500, fontSize = 12.sp)
                            }
                            Text("Putar", color = Emerald, fontWeight = FontWeight.SemiBold, fontSize = 12.sp)
                        }
                    }
                    Catatan("Ketuk satu pilihan untuk memilih sekaligus mendengar contohnya. Nada juga bisa diubah di Pengaturan HP > Aplikasi > EcoWin > Notifikasi.", Modifier.padding(top = 4.dp))
                }
            }
        }
    }
}

@Composable
private fun Catatan(teks: String, modifier: Modifier = Modifier) {
    Text(teks, color = Slate500, fontSize = 12.sp, modifier = modifier)
}
