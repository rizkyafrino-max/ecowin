package id.ecowin.app

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.Icon
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.launch

/** Alasan penolakan minimal 5 karakter (sama dengan aturan server). */
const val MIN_ALASAN_TOLAK = 5

fun alasanTolakValid(alasan: String): Boolean = alasan.trim().length >= MIN_ALASAN_TOLAK

/**
 * Daftar pendaftaran nasabah yang menunggu verifikasi (tahap 2). Petugas memeriksa nama, nomor HP, dan alamat,
 * lalu memverifikasi atau menolak dengan alasan. Hak akses (hanya Bank Sampah sendiri) ditegakkan server.
 */
@Composable
fun VerifikasiNasabahScreen(client: ApiClient, onBack: () -> Unit, onBerubah: () -> Unit) {
    androidx.activity.compose.BackHandler(onBack = onBack)
    val daftar = rememberLoadable(client) { client.api.daftarNasabah(statusVerifikasi = "pending", status = "aktif").data }
    val scope = rememberCoroutineScope()
    var sibukId by remember { mutableStateOf<Long?>(null) }
    var pesan by remember { mutableStateOf<Pair<String, Boolean>?>(null) }
    var menolak by remember { mutableStateOf<NasabahDto?>(null) }
    var konfirmasi by remember { mutableStateOf<NasabahDto?>(null) }

    fun selesai(teks: String) {
        pesan = teks to true
        sibukId = null
        daftar.reload()
        onBerubah()
    }

    Column(
        Modifier.fillMaxSize().background(Color(0xFFF8FAFC)).verticalScroll(rememberScrollState()).padding(horizontal = 16.dp, vertical = 20.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        Row(Modifier.clickable(onClick = onBack).padding(vertical = 4.dp), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            Icon(Lucide.ArrowLeft, contentDescription = "Kembali", tint = Slate900, modifier = Modifier.size(20.dp))
            Text("Verifikasi nasabah", fontSize = 20.sp, fontWeight = FontWeight.Bold, color = Slate900)
        }
        Text("Periksa data pendaftar sebelum memverifikasi. Nasabah yang belum diverifikasi belum bisa bertransaksi.", color = Slate500, fontSize = 13.sp)
        pesan?.let { PesanStatus(it.first, it.second) }

        LoadableContent(daftar) { list ->
            if (list.isEmpty()) EmptyText("Tidak ada pendaftaran menunggu. Semua pendaftar sudah ditangani.")
            list.forEach { n ->
                EcoCard {
                    Text(n.nama ?: "-", fontWeight = FontWeight.Bold, fontSize = 16.sp, color = Slate900)
                    LabelValue("No. HP", n.noHp ?: "-")
                    LabelValue("Alamat RT/RW", n.alamatRtRw ?: "-")
                    LabelValue("Mendaftar", tanggal(n.createdAt, withTime = false))
                    Row(Modifier.fillMaxWidth().padding(top = 8.dp), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                        OutlinedButton(
                            onClick = { menolak = n }, enabled = sibukId == null, modifier = Modifier.weight(1f),
                            shape = RoundedCornerShape(50),
                        ) { Text("Tolak", color = Red, fontWeight = FontWeight.SemiBold) }
                        Button(
                            onClick = { konfirmasi = n }, enabled = sibukId == null, modifier = Modifier.weight(1f),
                            shape = RoundedCornerShape(50),
                        ) { Text(if (sibukId == n.id) "Memproses…" else "Verifikasi", fontWeight = FontWeight.SemiBold) }
                    }
                }
            }
        }
    }

    konfirmasi?.let { n ->
        AlertDialog(
            onDismissRequest = { konfirmasi = null },
            title = { Text("Verifikasi ${n.nama ?: "nasabah"}?") },
            text = { Text("Pastikan nama, nomor HP, dan alamat sudah sesuai. Setelah diverifikasi, nasabah bisa bertransaksi dan memakai semua fitur.", fontSize = 14.sp) },
            confirmButton = {
                TextButton(onClick = {
                    konfirmasi = null
                    sibukId = n.id
                    pesan = null
                    scope.launch {
                        try {
                            client.api.verifikasiNasabah(n.id)
                            selesai("${n.nama ?: "Nasabah"} sudah diverifikasi.")
                        } catch (e: Exception) {
                            pesan = client.errorMessage(e) to false
                            sibukId = null
                        }
                    }
                }) { Text("Ya, verifikasi", fontWeight = FontWeight.Bold) }
            },
            dismissButton = { TextButton(onClick = { konfirmasi = null }) { Text("Batal") } },
        )
    }

    menolak?.let { n ->
        var alasan by remember(n.id) { mutableStateOf("") }
        AlertDialog(
            onDismissRequest = { menolak = null },
            title = { Text("Tolak pendaftaran ${n.nama ?: ""}?") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    Text("Akun akan dinonaktifkan dan nasabah tidak bisa masuk. Alasanmu dikirim ke nasabah dan tercatat.", fontSize = 13.sp, color = Slate500)
                    EcoTextField(
                        alasan, { alasan = it.take(500) }, label = { Text("Alasan penolakan") },
                        modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(14.dp),
                    )
                }
            },
            confirmButton = {
                TextButton(enabled = alasanTolakValid(alasan), onClick = {
                    menolak = null
                    sibukId = n.id
                    pesan = null
                    scope.launch {
                        try {
                            client.api.tolakNasabah(n.id, TolakNasabahRequest(alasan.trim()))
                            selesai("Pendaftaran ${n.nama ?: "nasabah"} ditolak.")
                        } catch (e: Exception) {
                            pesan = client.errorMessage(e) to false
                            sibukId = null
                        }
                    }
                }) { Text("Tolak", color = Red, fontWeight = FontWeight.Bold) }
            },
            dismissButton = { TextButton(onClick = { menolak = null }) { Text("Batal") } },
        )
    }
}
