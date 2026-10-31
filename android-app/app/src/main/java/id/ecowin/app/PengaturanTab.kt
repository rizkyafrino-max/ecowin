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
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.launch

private fun deskripsi(n: NotifikasiHp.Nada) = when (n) {
    NotifikasiHp.Nada.Ecowin -> "Tiga denting naik, nada khas EcoWin"
    NotifikasiHp.Nada.Daun -> "Dua nada kayu yang lembut"
    NotifikasiHp.Nada.Koin -> "Dua \"ting\" cepat yang ceria"
    NotifikasiHp.Nada.Sistem -> "Nada notifikasi bawaan HP Anda"
}

/**
 * Halaman Pengaturan: daftar berkelompok ala aplikasi modern. Tiap kelompok adalah satu kartu putih berisi baris-baris
 * dengan ikon berlatar lembut, judul, keterangan, dan kontrol di kanan; catatan kecil ada di bawah kartu.
 *
 * Pemberitahuan HP mati secara bawaan dan hanya menyala atas pilihan nasabah. Isi pesan disembunyikan di layar kunci.
 * Izin pemberitahuan Android 13+ baru diminta saat saklar dinyalakan, bukan saat aplikasi dibuka.
 */
@Composable
fun PengaturanTab(onLaporBug: () -> Unit) {
    val konteks = LocalContext.current
    var aktif by remember { mutableStateOf(NotifikasiHp.aktif(konteks) && NotifikasiHp.izinDiberikan(konteks)) }
    var ditolak by remember { mutableStateOf(false) }
    var nada by remember { mutableStateOf(NotifikasiHp.nada(konteks)) }
    val scope = androidx.compose.runtime.rememberCoroutineScope()

    // Acuan notifikasi ditetapkan SEBELUM token didaftarkan: kabar yang sudah ada tidak dibunyikan, kabar pertama sesudahnya langsung tampil.
    val nyalakan: () -> Unit = {
        NotifikasiHp.nyalakan(konteks)
        aktif = true
        ditolak = false
        scope.launch { tetapkanAcuanNotifikasi(konteks); Push.daftarkan(konteks) }
    }
    val minta = androidx.activity.compose.rememberLauncherForActivityResult(androidx.activity.result.contract.ActivityResultContracts.RequestPermission()) { ok ->
        if (ok) nyalakan() else ditolak = true
    }
    val ubahNotifikasi: (Boolean) -> Unit = { nyala ->
        if (!nyala) { scope.launch { Push.lepas(konteks) }; NotifikasiHp.berhenti(konteks); aktif = false }
        else if (NotifikasiHp.izinDiberikan(konteks)) nyalakan()
        else minta.launch(android.Manifest.permission.POST_NOTIFICATIONS)
    }
    val goyang = PengaturanApp.goyangAktif.value
    val situs = androidx.compose.ui.platform.LocalUriHandler.current

    TabList {
        item {
            Kelompok(
                "Pemberitahuan",
                catatan = "Dicek tiap ±15 menit saat ada koneksi internet. Di layar kunci isi pesan disembunyikan; nominal baru terlihat setelah HP dibuka. Mati otomatis saat Anda keluar akun.",
            ) {
                Baris(
                    Lucide.Bell, "Pemberitahuan di HP", "Kabar setoran dan penarikan walau aplikasi tertutup",
                    peran = Role.Switch, onClick = { ubahNotifikasi(!aktif) },
                ) { Saklar(aktif) }
                if (ditolak) {
                    Pemisah()
                    Text(
                        "Izin pemberitahuan ditolak. Aktifkan lewat Pengaturan HP > Aplikasi > EcoWin > Notifikasi.",
                        color = Red, fontSize = 12.sp, lineHeight = 17.sp, modifier = Modifier.padding(horizontal = 16.dp, vertical = 12.dp),
                    )
                }
            }
        }

        if (aktif) {
            item {
                Kelompok(
                    "Nada notifikasi",
                    catatan = "Ketuk satu pilihan untuk memilih sekaligus mendengar contohnya. Nada juga bisa diubah di Pengaturan HP > Aplikasi > EcoWin > Notifikasi.",
                ) {
                    NotifikasiHp.Nada.entries.forEachIndexed { i, n ->
                        if (i > 0) Pemisah()
                        val dipilih = n == nada
                        Baris(
                            Lucide.Volume2, n.label, deskripsi(n),
                            warnaIkon = if (dipilih) Color.White else Slate500, latarIkon = if (dipilih) Emerald else LatarNetral,
                            dipilih = dipilih, peran = Role.RadioButton,
                            onClick = { nada = n; NotifikasiHp.setNada(konteks, n); NotifikasiHp.pratinjau(konteks, n) },
                        ) {
                            if (dipilih) Icon(Lucide.Check, contentDescription = "Dipilih", tint = Emerald, modifier = Modifier.size(20.dp))
                            else Icon(Lucide.Play, contentDescription = "Putar contoh", tint = Slate400, modifier = Modifier.size(18.dp))
                        }
                    }
                }
            }
        }

        item {
            Kelompok("Bantuan", catatan = "Sensor gerak hanya dibaca saat aplikasi terlihat di layar.") {
                Baris(
                    Lucide.Vibrate, "Goyang untuk lapor bug", "Guncang HP tiga kali saat aplikasi terbuka",
                    warnaIkon = Color(0xFF2563EB), latarIkon = Color(0xFFEFF6FF),
                    peran = Role.Switch, onClick = { PengaturanApp.setGoyang(konteks, !goyang) },
                ) { Saklar(goyang) }
                Pemisah()
                Baris(
                    Lucide.Bug, "Lapor bug", "Kirim masalah beserta foto bukti",
                    warnaIkon = Color(0xFFD97706), latarIkon = Color(0xFFFFFBEB), onClick = onLaporBug,
                ) { PanahBaris() }
            }
        }

        item {
            Kelompok("Tentang") {
                Baris(Lucide.Info, "Versi aplikasi", "EcoWin Bank Sampah Digital", warnaIkon = Slate500, latarIkon = LatarNetral) {
                    Text(BuildConfig.VERSION_NAME, color = Slate500, fontSize = 14.sp, fontWeight = FontWeight.SemiBold)
                }
                Pemisah()
                Baris(
                    Lucide.UserRound, "Dibuat oleh Rizky", "rizky.pplgku.my.id", warnaIkon = Emerald, latarIkon = EmeraldSoft,
                    onClick = { runCatching { situs.openUri(URL_PEMBUAT) } },
                ) { TautanLuar() }
            }
        }
    }
}
