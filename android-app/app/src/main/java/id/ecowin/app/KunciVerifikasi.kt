package id.ecowin.app

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Icon
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.semantics.clearAndSetSemantics
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.launch

/**
 * Nasabah yang mendaftar sendiri berstatus "pending" sampai petugas Bank Sampah memverifikasinya. Selama itu halaman data dan
 * layanan disensor (diganti layar terkunci) dan server juga menolak permintaannya (403, code "belum_diverifikasi"), jadi aturan ini
 * tidak bisa dilewati dengan memanggil API langsung.
 */
fun akunTerkunci(user: UserDto): Boolean = user.role == "nasabah" && user.nasabah?.statusVerifikasi != "verified"

/** Halaman yang disensor selama akun terkunci. Profil, Notifikasi, Pengaturan, Lapor bug, dan menu Semua Fitur tetap terbuka. */
fun tabTerkunci(tab: Tab): Boolean = tab in setOf(Tab.Beranda, Tab.Transaksi, Tab.Organik, Tab.Saldo, Tab.Kartu)

/**
 * Pengganti isi halaman yang terkunci: kartu pesan + kerangka abu-abu (bukan isi asli yang diburamkan), jadi tidak ada data yang
 * dimuat atau bocor lewat blur yang tidak didukung di Android lama. Status dicek ulang otomatis oleh MainScreen dan lewat tombol.
 */
@Composable
fun LayarTerkunci(client: ApiClient, onUserChanged: (UserDto) -> Unit) {
    val scope = rememberCoroutineScope()
    var memeriksa by remember { mutableStateOf(false) }
    var pesan by remember { mutableStateOf<String?>(null) }

    TabList {
        item {
            Column(
                Modifier.fillMaxWidth().background(Color.White, RoundedCornerShape(24.dp)).border(1.dp, Slate200, RoundedCornerShape(24.dp)).padding(20.dp),
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.spacedBy(10.dp),
            ) {
                Box(Modifier.size(64.dp).background(AmberSoft, CircleShape), contentAlignment = Alignment.Center) {
                    Icon(Lucide.Lock, contentDescription = null, tint = Amber, modifier = Modifier.size(30.dp))
                }
                Text("Halaman ini terkunci", fontWeight = FontWeight.ExtraBold, fontSize = 20.sp, color = Slate900, textAlign = TextAlign.Center)
                Text(
                    "Akunmu masih menunggu verifikasi petugas Bank Sampah. Setelah diverifikasi, halaman ini terbuka sendiri dan kamu bisa mulai bertransaksi.",
                    color = Slate500, fontSize = 14.sp, lineHeight = 20.sp, textAlign = TextAlign.Center,
                )
                pesan?.let { Text(it, color = Slate500, fontSize = 13.sp, textAlign = TextAlign.Center, fontWeight = FontWeight.SemiBold) }
                PrimaryButton("Periksa status", loading = memeriksa, icon = Lucide.ShieldCheck) {
                    memeriksa = true
                    pesan = null
                    scope.launch {
                        try {
                            val baru = client.api.me().data
                            if (akunTerkunci(baru)) pesan = "Akunmu belum diverifikasi. Coba lagi nanti ya."
                            onUserChanged(baru)
                        } catch (e: Exception) {
                            pesan = client.errorMessage(e)
                        } finally {
                            memeriksa = false
                        }
                    }
                }
            }
        }
        item { KerangkaTerkunci() }
    }
}

/** Bentuk kosong menyerupai halaman (kartu, ubin, baris), hanya hiasan: tidak terbaca oleh pembaca layar dan tidak berisi data. */
@Composable
private fun KerangkaTerkunci() {
    Column(
        Modifier.fillMaxWidth().alpha(0.55f).clearAndSetSemantics { },
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        Box(Modifier.fillMaxWidth().height(140.dp).background(Slate200, RoundedCornerShape(24.dp)))
        Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
            repeat(2) { Box(Modifier.weight(1f).height(84.dp).background(Slate200, RoundedCornerShape(20.dp))) }
        }
        repeat(4) {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp), verticalAlignment = Alignment.CenterVertically) {
                Box(Modifier.size(44.dp).background(Slate200, RoundedCornerShape(16.dp)))
                Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                    Box(Modifier.fillMaxWidth(0.7f).height(14.dp).background(Slate200, RoundedCornerShape(50)))
                    Box(Modifier.fillMaxWidth(0.45f).height(11.dp).background(Slate200, RoundedCornerShape(50)))
                }
                Box(Modifier.width(56.dp).height(14.dp).background(Slate200, RoundedCornerShape(50)))
            }
        }
    }
}
