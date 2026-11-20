package id.ecowin.app

import androidx.compose.foundation.background
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
 * Beranda petugas (tahap 1): ringkasan pekerjaan yang menunggu. Verifikasi nasabah, scan QR + catat setoran,
 * dan persetujuan penarikan menyusul di tahap berikutnya.
 */
@Composable
fun PetugasScreen(user: UserDto, client: ApiClient, onLogout: () -> Unit) {
    val ringkasan = rememberLoadable(client) { client.api.petugasRingkasan() }

    Column(
        Modifier.fillMaxSize().background(Color(0xFFF8FAFC)).verticalScroll(rememberScrollState()).padding(horizontal = 16.dp, vertical = 24.dp),
        verticalArrangement = Arrangement.spacedBy(14.dp),
    ) {
        Text("${sapaan()},", color = Slate500, fontSize = 14.sp)
        Text(user.nama ?: "Petugas", color = Slate900, fontSize = 22.sp, fontWeight = FontWeight.Bold)

        LoadableContent(ringkasan) { r ->
            r.bankSampah?.let { Text("Bank Sampah ${it.nama.orEmpty()}", color = Slate500, fontSize = 13.sp) }
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                StatTile("Menunggu verifikasi", r.menungguVerifikasi.toString(), Modifier.weight(1f), warn = r.menungguVerifikasi > 0)
                StatTile("Penarikan menunggu", r.penarikanMenunggu.toString(), Modifier.weight(1f), warn = r.penarikanMenunggu > 0)
            }
            StatTile("Setoran hari ini", "${r.setoranHariIni.jumlah} setoran", Modifier.fillMaxWidth(), hint = rupiah(r.setoranHariIni.nilaiRupiah))
        }

        Spacer(Modifier.height(4.dp))
        Text("Verifikasi nasabah, scan QR, dan persetujuan penarikan segera hadir di aplikasi ini.", color = Slate500, fontSize = 13.sp)
        PrimaryButton("Keluar", onClick = onLogout)
    }
}
