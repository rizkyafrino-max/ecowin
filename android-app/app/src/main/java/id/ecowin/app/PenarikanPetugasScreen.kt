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

/** Penarikan yang menunggu keputusan petugas. */
fun penarikanMenunggu(p: PenarikanDto): Boolean = p.status == "pending"

/** Penarikan yang sudah disetujui (saldo sudah dipotong) dan tinggal menyerahkan uang ke nasabah. */
fun penarikanPerluDiserahkan(p: PenarikanDto): Boolean = p.status == "approved"

private enum class FilterPenarikan(val status: String, val label: String) {
    Menunggu("pending", "Menunggu keputusan"),
    Diserahkan("approved", "Siap diserahkan"),
}

private sealed interface TindakanPenarikan {
    data class Setujui(val p: PenarikanDto) : TindakanPenarikan
    data class Tolak(val p: PenarikanDto) : TindakanPenarikan
    data class Serahkan(val p: PenarikanDto) : TindakanPenarikan
}

/**
 * Tahap 4: petugas memutuskan permintaan penarikan saldo nasabah di Bank Sampahnya.
 * Alur server: menunggu -> disetujui (saldo nasabah dipotong) -> selesai (uang diserahkan), atau ditolak dengan alasan.
 */
@Composable
fun PenarikanPetugasScreen(client: ApiClient, onBack: () -> Unit, onBerubah: () -> Unit) {
    androidx.activity.compose.BackHandler(onBack = onBack)
    var filter by remember { mutableStateOf(FilterPenarikan.Menunggu) }
    val daftar = rememberLoadable(client, filter) { client.api.daftarPenarikanPetugas(filter.status).data }
    val scope = rememberCoroutineScope()
    var sibuk by remember { mutableStateOf(false) }
    var pesan by remember { mutableStateOf<Pair<String, Boolean>?>(null) }
    var aksi by remember { mutableStateOf<TindakanPenarikan?>(null) }

    fun jalankan(sukses: String, blok: suspend () -> Unit) {
        sibuk = true
        pesan = null
        scope.launch {
            try {
                blok()
                pesan = sukses to true
                daftar.reload()
                onBerubah()
            } catch (e: Exception) {
                pesan = client.errorMessage(e) to false
            } finally {
                sibuk = false
            }
        }
    }

    Column(
        Modifier.fillMaxSize().background(Color(0xFFF8FAFC)).verticalScroll(rememberScrollState()).padding(horizontal = 16.dp, vertical = 20.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        Row(Modifier.clickable(onClick = onBack).padding(vertical = 4.dp), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            Icon(Lucide.ArrowLeft, contentDescription = "Kembali", tint = Slate900, modifier = Modifier.size(20.dp))
            Text("Penarikan saldo", fontSize = 20.sp, fontWeight = FontWeight.Bold, color = Slate900)
        }
        Text("Setujui atau tolak permintaan penarikan. Saldo nasabah dipotong saat kamu menyetujui, lalu tandai selesai setelah uangnya kamu serahkan.", color = Slate500, fontSize = 13.sp)

        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            FilterPenarikan.entries.forEach { f ->
                val pilih = filter == f
                Text(
                    f.label, fontSize = 13.sp, fontWeight = FontWeight.SemiBold, color = if (pilih) Color.White else Slate900,
                    modifier = Modifier
                        .background(if (pilih) Emerald else Color.White, RoundedCornerShape(50))
                        .clickable(role = androidx.compose.ui.semantics.Role.Tab) { filter = f; pesan = null }
                        .padding(horizontal = 14.dp, vertical = 8.dp),
                )
            }
        }
        pesan?.let { PesanStatus(it.first, it.second) }

        LoadableContent(daftar) { list ->
            if (list.isEmpty()) {
                EmptyText(if (filter == FilterPenarikan.Menunggu) "Tidak ada penarikan menunggu. Semua permintaan sudah diputuskan." else "Tidak ada penarikan yang menunggu diserahkan.")
            }
            list.forEach { p ->
                EcoCard {
                    Text(p.nasabah ?: "Nasabah", fontWeight = FontWeight.Bold, fontSize = 16.sp, color = Slate900)
                    LabelValue("No. nasabah", p.nomorNasabah ?: "-")
                    LabelValue("Jumlah", rupiah(p.jumlah))
                    LabelValue("Diajukan", tanggal(p.createdAt, withTime = true))
                    if (!p.catatan.isNullOrBlank()) LabelValue("Catatan", p.catatan)
                    when {
                        penarikanMenunggu(p) -> Row(Modifier.fillMaxWidth().padding(top = 8.dp), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                            OutlinedButton(onClick = { aksi = TindakanPenarikan.Tolak(p) }, enabled = !sibuk, modifier = Modifier.weight(1f), shape = RoundedCornerShape(50)) {
                                Text("Tolak", color = Red, fontWeight = FontWeight.SemiBold)
                            }
                            Button(onClick = { aksi = TindakanPenarikan.Setujui(p) }, enabled = !sibuk, modifier = Modifier.weight(1f), shape = RoundedCornerShape(50)) {
                                Text("Setujui", fontWeight = FontWeight.SemiBold)
                            }
                        }
                        penarikanPerluDiserahkan(p) -> PrimaryButton("Uang sudah diserahkan", enabled = !sibuk, icon = Lucide.Check) { aksi = TindakanPenarikan.Serahkan(p) }
                    }
                }
            }
        }
    }

    when (val a = aksi) {
        is TindakanPenarikan.Setujui -> AlertDialog(
            onDismissRequest = { aksi = null },
            title = { Text("Setujui penarikan ${rupiah(a.p.jumlah)}?") },
            text = { Text("Saldo ${a.p.nasabah ?: "nasabah"} langsung dipotong ${rupiah(a.p.jumlah)}. Siapkan uangnya untuk diserahkan, lalu tandai selesai di sini.", fontSize = 14.sp) },
            confirmButton = {
                TextButton(onClick = {
                    aksi = null
                    jalankan("Penarikan disetujui. Serahkan uangnya ke nasabah.") { client.api.setujuiPenarikan(a.p.id) }
                }) { Text("Ya, setujui", fontWeight = FontWeight.Bold) }
            },
            dismissButton = { TextButton(onClick = { aksi = null }) { Text("Batal") } },
        )
        is TindakanPenarikan.Serahkan -> AlertDialog(
            onDismissRequest = { aksi = null },
            title = { Text("Uang sudah diserahkan?") },
            text = { Text("Pastikan ${rupiah(a.p.jumlah)} sudah kamu serahkan ke ${a.p.nasabah ?: "nasabah"}. Penarikan akan ditandai selesai dan nasabah diberi tahu.", fontSize = 14.sp) },
            confirmButton = {
                TextButton(onClick = {
                    aksi = null
                    jalankan("Penarikan selesai.") { client.api.selesaikanPenarikan(a.p.id) }
                }) { Text("Ya, sudah", fontWeight = FontWeight.Bold) }
            },
            dismissButton = { TextButton(onClick = { aksi = null }) { Text("Batal") } },
        )
        is TindakanPenarikan.Tolak -> {
            var alasan by remember(a.p.id) { mutableStateOf("") }
            AlertDialog(
                onDismissRequest = { aksi = null },
                title = { Text("Tolak penarikan ${rupiah(a.p.jumlah)}?") },
                text = {
                    Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                        Text("Saldo nasabah tidak dipotong. Alasanmu dikirim ke nasabah.", fontSize = 13.sp, color = Slate500)
                        EcoTextField(
                            alasan, { alasan = it.take(500) }, label = { Text("Alasan penolakan") },
                            modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(14.dp),
                        )
                    }
                },
                confirmButton = {
                    TextButton(enabled = alasanTolakValid(alasan), onClick = {
                        aksi = null
                        jalankan("Penarikan ditolak.") { client.api.tolakPenarikan(a.p.id, TolakPenarikanRequest(alasan.trim())) }
                    }) { Text("Tolak", color = Red, fontWeight = FontWeight.Bold) }
                },
                dismissButton = { TextButton(onClick = { aksi = null }) { Text("Batal") } },
            )
        }
        null -> Unit
    }
}
