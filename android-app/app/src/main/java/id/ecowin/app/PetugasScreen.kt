package id.ecowin.app

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

/** Peran yang boleh memakai aplikasi ini. Admin tetap memakai dashboard web. */
fun peranBolehMasuk(role: String?): Boolean = role == "nasabah" || role == "petugas"

fun adalahPetugas(user: UserDto): Boolean = user.role == "petugas"

/**
 * Beranda petugas: ringkasan pekerjaan yang menunggu, lalu verifikasi nasabah, scan QR + catat setoran,
 * dan persetujuan penarikan saldo.
 */
@Composable
fun PetugasScreen(user: UserDto, client: ApiClient, onLogout: () -> Unit) {
    val ringkasan = rememberLoadable(client) { client.api.petugasRingkasan() }
    var verifikasi by remember { mutableStateOf(false) }
    var setor by remember { mutableStateOf(false) }
    var penarikan by remember { mutableStateOf(false) }

    if (penarikan) {
        PenarikanPetugasScreen(client, onBack = { penarikan = false }, onBerubah = { ringkasan.reload() })
        return
    }

    if (setor) {
        SetorScreen(client, onBack = { setor = false }, onBerubah = { ringkasan.reload() })
        return
    }

    if (verifikasi) {
        VerifikasiNasabahScreen(client, onBack = { verifikasi = false }, onBerubah = { ringkasan.reload() })
        return
    }

    Column(
        Modifier.fillMaxSize().background(Color(0xFFF8FAFC)).verticalScroll(rememberScrollState()).padding(horizontal = 16.dp, vertical = 24.dp),
        verticalArrangement = Arrangement.spacedBy(14.dp),
    ) {
        Text("${sapaan()},", color = Slate500, fontSize = 14.sp)
        Text(user.nama ?: "Petugas", color = Slate900, fontSize = 22.sp, fontWeight = FontWeight.Bold)

        LoadableContent(ringkasan) { r ->
            r.bankSampah?.let { Text("Bank Sampah ${it.nama.orEmpty()}", color = Slate500, fontSize = 13.sp) }
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                StatTile("Menunggu verifikasi", r.menungguVerifikasi.toString(), Modifier.weight(1f).clickable { verifikasi = true }, hint = "Ketuk untuk memeriksa", warn = r.menungguVerifikasi > 0)
                StatTile("Penarikan menunggu", r.penarikanMenunggu.toString(), Modifier.weight(1f).clickable { penarikan = true }, hint = "Ketuk untuk memeriksa", warn = r.penarikanMenunggu > 0)
            }
            StatTile("Setoran hari ini", "${r.setoranHariIni.jumlah} setoran", Modifier.fillMaxWidth(), hint = rupiah(r.setoranHariIni.nilaiRupiah))
        }

        Spacer(Modifier.height(4.dp))
        PrimaryButton("Scan QR dan catat setoran", icon = Lucide.QrCode, onClick = { setor = true })
        PrimaryButton("Verifikasi nasabah", onClick = { verifikasi = true })
        PrimaryButton("Penarikan saldo", onClick = { penarikan = true })
        PrimaryButton("Keluar", onClick = onLogout)
    }
}
