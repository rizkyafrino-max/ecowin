package id.ecowin.app

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
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

private data class Fitur(val tab: Tab, val icon: ImageVector, val label: String, val text: String)

private val Grup: List<Pair<String, List<Fitur>>> = listOf(
    "UTAMA" to listOf(
        Fitur(Tab.Beranda, Tab.Beranda.icon, "Beranda", "Ringkasan saldo, setoran, dan aktivitas terbaru."),
        Fitur(Tab.Transaksi, Tab.Transaksi.icon, "Transaksi", "Riwayat setoran sampah anorganik dan nilainya."),
        Fitur(Tab.Organik, Tab.Organik.icon, "Organik", "Setoran dan aktivitas organik: BioporiPrint, estimasi kompos."),
    ),
    "KEUANGAN" to listOf(
        Fitur(Tab.Saldo, Tab.Saldo.icon, "Saldo", "Saldo, saldo tersedia, dan riwayat mutasi."),
        Fitur(Tab.Saldo, Lucide.ArrowDownToLine, "Tarik Saldo", "Ajukan penarikan dan pantau statusnya."),
    ),
    "AKUN" to listOf(
        Fitur(Tab.Profil, Lucide.QrCode, "QR Saya", "Tunjukkan QR ke petugas saat setor sampah."),
        Fitur(Tab.Organik, Lucide.MapPin, "Peta BioporiPrint", "Lihat titik BioporiPrint di sekitar Anda."),
        Fitur(Tab.Profil, Lucide.UserRound, "Profil", "Data diri, Bank Sampah, dan keluar dari akun."),
    ),
)

/** Halaman "Semua Fitur": sama dengan halaman Semua Fitur di Web. */
@Composable
fun FiturTab(onNavigate: (Tab) -> Unit) {
    TabList {
        item { Text("Semua yang bisa Anda lakukan di EcoWin.", color = Slate500, fontSize = 14.sp) }
        Grup.forEach { (judul, daftar) ->
            item { Text(judul, color = Slate500, fontSize = 12.sp, fontWeight = FontWeight.Bold, letterSpacing = 1.sp, modifier = Modifier.padding(top = 4.dp)) }
            daftar.forEach { f ->
                item {
                    Row(
                        Modifier.fillMaxWidth()
                            .background(Color.White, RoundedCornerShape(20.dp))
                            .border(1.dp, Slate200, RoundedCornerShape(20.dp))
                            .clickable { onNavigate(f.tab) }
                            .padding(16.dp),
                        horizontalArrangement = Arrangement.spacedBy(14.dp),
                        verticalAlignment = Alignment.Top,
                    ) {
                        Box(Modifier.size(48.dp).background(EmeraldSoft, RoundedCornerShape(16.dp)), contentAlignment = Alignment.Center) {
                            Icon(f.icon, contentDescription = null, tint = Emerald, modifier = Modifier.size(24.dp))
                        }
                        Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(2.dp)) {
                            Text(f.label, fontWeight = FontWeight.Bold, color = Slate900)
                            Text(f.text, color = Slate500, fontSize = 12.sp, lineHeight = 17.sp)
                        }
                    }
                }
            }
        }
    }
}
