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
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Icon
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
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.launch

/**
 * Tahap 3: petugas memindai QR Card nasabah lalu mencatat setoran anorganik (jenis sampah + berat).
 * Harga dihitung server dari harga yang berlaku; saldo nasabah bertambah dan nasabah mendapat notifikasi.
 */
@Composable
fun SetorScreen(client: ApiClient, onBack: () -> Unit, onBerubah: () -> Unit) {
    androidx.activity.compose.BackHandler(onBack = onBack)
    val konteks = LocalContext.current
    val scope = rememberCoroutineScope()
    val harga = rememberLoadable(client) { client.api.harga().data }

    var nasabah by remember { mutableStateOf<NasabahDto?>(null) }
    var mencari by remember { mutableStateOf(false) }
    var jenisId by remember { mutableStateOf<Long?>(null) }
    var berat by remember { mutableStateOf("") }
    var konfirmasi by remember { mutableStateOf(false) }
    var menyimpan by remember { mutableStateOf(false) }
    var pesan by remember { mutableStateOf<Pair<String, Boolean>?>(null) }
    var hasil by remember { mutableStateOf<SetoranAnorganikDto?>(null) }

    fun ulang() {
        nasabah = null
        jenisId = null
        berat = ""
        hasil = null
    }

    fun pindai() {
        pesan = null
        hasil = null
        mulaiScanQr(
            konteks,
            onHasil = { isi ->
                val token = isi.trim()
                if (!tokenQrValid(token)) {
                    pesan = "QR ini bukan kartu nasabah EcoWin." to false
                } else {
                    mencari = true
                    scope.launch {
                        try {
                            nasabah = client.api.scanNasabah(token).data
                            jenisId = null
                            berat = ""
                        } catch (e: Exception) {
                            pesan = (if (e is retrofit2.HttpException && e.code() == 404) "Nasabah tidak ditemukan di Bank Sampahmu." else client.errorMessage(e)) to false
                        } finally {
                            mencari = false
                        }
                    }
                }
            },
            onGagal = { pesan = it to false },
        )
    }

    Column(
        Modifier.fillMaxSize().background(Color(0xFFF8FAFC)).verticalScroll(rememberScrollState()).padding(horizontal = 16.dp, vertical = 20.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        Row(Modifier.clickable(onClick = onBack).padding(vertical = 4.dp), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            Icon(Lucide.ArrowLeft, contentDescription = "Kembali", tint = Slate900, modifier = Modifier.size(20.dp))
            Text("Catat setoran", fontSize = 20.sp, fontWeight = FontWeight.Bold, color = Slate900)
        }
        pesan?.let { PesanStatus(it.first, it.second) }

        val setoran = hasil
        val n = nasabah
        when {
            setoran != null -> {
                EcoCard(accent = Emerald) {
                    Text("Setoran tercatat", fontWeight = FontWeight.Bold, fontSize = 18.sp, color = Slate900)
                    LabelValue("Nasabah", setoran.nasabah ?: n?.nama ?: "-")
                    LabelValue("Jenis", setoran.jenisSampah ?: "-")
                    LabelValue("Berat", kg(setoran.beratKg))
                    LabelValue("Harga per kg", rupiah(setoran.hargaPerKg))
                    LabelValue("Nilai masuk saldo", rupiah(setoran.nilaiRupiah))
                }
                PrimaryButton("Catat setoran berikutnya", icon = Lucide.QrCode) {
                    ulang()
                    pindai()
                }
            }
            n == null -> {
                EcoCard {
                    Text("Pindai kartu nasabah", fontWeight = FontWeight.Bold, fontSize = 16.sp, color = Slate900)
                    Text("Minta nasabah menunjukkan QR Card di aplikasinya, lalu arahkan kamera ke QR tersebut.", color = Slate500, fontSize = 13.sp)
                }
                PrimaryButton("Scan QR nasabah", loading = mencari, icon = Lucide.QrCode) { pindai() }
            }
            else -> {
                EcoCard {
                    Text(n.nama ?: "-", fontWeight = FontWeight.Bold, fontSize = 18.sp, color = Slate900)
                    LabelValue("No. nasabah", n.nomorNasabah ?: "-")
                    LabelValue("Saldo", rupiah(n.saldo))
                    LabelValue("Verifikasi", if (n.statusVerifikasi == "verified") "Terverifikasi" else "Belum diverifikasi")
                }
                if (!bolehDisetori(n)) {
                    PesanStatus(
                        if (n.status != "aktif") "Akun nasabah ini nonaktif, setoran tidak bisa dicatat."
                        else "Nasabah ini belum diverifikasi. Verifikasi dulu lewat menu Verifikasi nasabah.",
                        false,
                    )
                    PrimaryButton("Scan nasabah lain", icon = Lucide.QrCode) {
                        ulang()
                        pindai()
                    }
                } else {
                    SectionTitle("Jenis sampah")
                    LoadableContent(harga) { kategori ->
                        kategori.forEach { k ->
                            if (k.jenisSampah.isNotEmpty()) {
                                Text(k.namaKategori, color = Slate500, fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
                                k.jenisSampah.forEach { j ->
                                    val dipilih = jenisId == j.id
                                    val acuan = j.harga.minByOrNull { it.minimalBerat }
                                    Row(
                                        Modifier.fillMaxWidth()
                                            .background(if (dipilih) EmeraldSoft else Color.White, RoundedCornerShape(14.dp))
                                            .clickable(role = androidx.compose.ui.semantics.Role.RadioButton) { jenisId = j.id }
                                            .padding(14.dp),
                                        horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically,
                                    ) {
                                        Text(j.namaJenis, fontWeight = if (dipilih) FontWeight.Bold else FontWeight.Medium, color = Slate900, fontSize = 14.sp)
                                        Text(acuan?.let { "${rupiah(it.hargaPerKg)}/kg" } ?: "-", color = Slate500, fontSize = 13.sp)
                                    }
                                }
                            }
                        }
                    }
                    EcoTextField(
                        berat, { berat = autoKoma(it).take(8) }, label = { Text("Berat (kg)") },
                        placeholder = { Text("mis. 2,5") }, singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                        supportingText = { Text("Harga dihitung otomatis oleh server sesuai tingkat berat.") },
                    )
                    PrimaryButton("Catat setoran", enabled = jenisId != null && beratSetoranValid(berat), loading = menyimpan) { konfirmasi = true }
                    TextButton(onClick = {
                        ulang()
                        pindai()
                    }, modifier = Modifier.fillMaxWidth()) { Text("Scan nasabah lain") }
                }
            }
        }
    }

    val dipindai = nasabah
    if (konfirmasi && dipindai != null) {
        val namaJenis = harga.data?.flatMap { it.jenisSampah }?.firstOrNull { it.id == jenisId }?.namaJenis ?: "-"
        AlertDialog(
            onDismissRequest = { konfirmasi = false },
            title = { Text("Catat setoran ini?") },
            text = { Text("${dipindai.nama ?: "Nasabah"}: $berat kg $namaJenis. Setoran yang sudah dicatat tidak bisa diubah langsung; perbaikannya lewat koreksi transaksi di panel web.", fontSize = 14.sp) },
            confirmButton = {
                TextButton(enabled = !menyimpan, onClick = {
                    konfirmasi = false
                    menyimpan = true
                    pesan = null
                    scope.launch {
                        try {
                            hasil = client.api.catatSetoran(SetoranAnorganikRequest(dipindai.id, jenisId ?: 0, keDouble(berat) ?: 0.0)).data
                            onBerubah()
                        } catch (e: Exception) {
                            pesan = client.errorMessage(e) to false
                        } finally {
                            menyimpan = false
                        }
                    }
                }) { Text("Ya, catat", fontWeight = FontWeight.Bold) }
            },
            dismissButton = { TextButton(onClick = { konfirmasi = false }) { Text("Batal") } },
        )
    }
}
