package id.ecowin.app

import android.graphics.BitmapFactory
import android.net.Uri
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.Image
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.RadioButton
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.ImageBitmap
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.asRequestBody
import okhttp3.RequestBody.Companion.toRequestBody
import java.io.File
import java.time.OffsetDateTime
import java.time.ZoneOffset
import java.time.format.DateTimeFormatter
import java.time.temporal.ChronoUnit

/**
 * Organik -> Aktivitas Biopori -> BioporiPrint.
 * Nasabah melaporkan sampah organik yang dimasukkan ke lubang Biopori, wajib dengan foto.
 * Status awal selalu "Menunggu" sampai diverifikasi petugas.
 */
@Composable
fun BioporiTab(client: ApiClient, refreshKey: Int) {
    var formOpen by rememberSaveable { mutableStateOf(false) }
    var localRefresh by remember { mutableStateOf(0) }
    val riwayat = rememberLoadable(client, refreshKey, localRefresh) { client.api.aktivitasBiopori().data }
    val lokasi = rememberLoadable(client, refreshKey) { client.api.lokasiBiopori().data }

    TabList {
        item {
            if (formOpen) {
                LaporBioporiForm(client, lokasi, onDone = { formOpen = false; localRefresh++ }, onCancel = { formOpen = false })
            } else {
                PrimaryButton("+ Lapor aktivitas Biopori") { formOpen = true }
            }
        }

        item {
            LoadableContent(lokasi) { list ->
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    SectionTitle("Lokasi Biopori & estimasi panen")
                    if (list.isEmpty()) EmptyText("Belum ada lokasi Biopori di Bank Sampah Anda.")
                    list.forEach { t ->
                        EcoCard {
                            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                                Text(t.namaLokasi ?: "Lokasi", fontWeight = FontWeight.SemiBold, modifier = Modifier.weight(1f))
                                StatusBadge(t.statusPanen)
                            }
                            if (t.bioporiprint) Text("Pipa BioporiPrint (3D print plastik daur ulang)", color = Slate500, fontSize = 12.sp)
                            LabelValue("Terakhir diisi", tanggal(t.terakhirDiisiAt, withTime = false))
                            LabelValue("Estimasi panen", tanggal(t.estimasiPanenAt, withTime = false))
                        }
                    }
                }
            }
        }

        item {
            LoadableContent(riwayat) { list ->
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    SectionTitle("Riwayat laporan")
                    if (list.isEmpty()) EmptyText("Belum ada laporan.")
                    list.forEach { BioporiRow(it) }
                }
            }
        }
    }
}

@Composable
private fun LaporBioporiForm(client: ApiClient, lokasi: Loadable<List<TitikBioporiDto>>, onDone: () -> Unit, onCancel: () -> Unit) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()

    var titikId by rememberSaveable { mutableStateOf<Long?>(null) }
    var jenis by rememberSaveable { mutableStateOf("") }
    var berat by rememberSaveable { mutableStateOf("") }
    var catatan by rememberSaveable { mutableStateOf("") }
    var pendingUri by rememberSaveable { mutableStateOf<String?>(null) }
    var fotoPath by rememberSaveable { mutableStateOf<String?>(null) }
    var preview by remember { mutableStateOf<ImageBitmap?>(null) }
    var waktu by rememberSaveable { mutableStateOf(OffsetDateTime.now().truncatedTo(ChronoUnit.MINUTES).toString()) }
    var error by remember { mutableStateOf<String?>(null) }
    var sending by remember { mutableStateOf(false) }

    val camera = rememberLauncherForActivityResult(ActivityResultContracts.TakePicture()) { ok ->
        val uri = pendingUri
        if (ok && uri != null) {
            scope.launch {
                try {
                    val file = withContext(Dispatchers.IO) { PhotoUtils.compress(context, Uri.parse(uri)) }
                    fotoPath = file.absolutePath
                    preview = BitmapFactory.decodeFile(file.absolutePath)?.asImageBitmap()
                    waktu = OffsetDateTime.now().truncatedTo(ChronoUnit.MINUTES).toString()
                } catch (e: Exception) {
                    error = e.message ?: "Gagal memproses foto."
                }
            }
        }
    }

    EcoCard {
        Text("Lapor aktivitas Biopori", fontWeight = FontWeight.Bold, fontSize = 16.sp)

        Text("Lokasi Biopori", color = Slate500, fontSize = 13.sp)
        val daftar = lokasi.data.orEmpty().filter { it.status == "aktif" }
        if (daftar.isEmpty()) EmptyText("Tidak ada lokasi Biopori aktif.")
        daftar.forEach { t ->
            Row(
                Modifier.fillMaxWidth().clickable { titikId = t.id },
                verticalAlignment = Alignment.CenterVertically,
            ) {
                RadioButton(selected = titikId == t.id, onClick = { titikId = t.id })
                Text(t.namaLokasi ?: "Lokasi #${t.id}")
            }
        }

        OutlinedTextField(jenis, { jenis = it.take(100) }, label = { Text("Jenis sampah organik") }, placeholder = { Text("Sisa sayur, daun, …") }, singleLine = true, modifier = Modifier.fillMaxWidth())
        OutlinedTextField(
            berat, { berat = it.filter { c -> c.isDigit() || c == '.' || c == ',' }.take(6) },
            label = { Text("Perkiraan berat (kg)") }, singleLine = true,
            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal), modifier = Modifier.fillMaxWidth(),
        )
        OutlinedTextField(catatan, { catatan = it.take(1000) }, label = { Text("Catatan (opsional)") }, modifier = Modifier.fillMaxWidth())

        LabelValue("Tanggal & waktu", tanggal(waktu))

        preview?.let {
            Image(it, contentDescription = "Foto bukti", contentScale = ContentScale.Crop, modifier = Modifier.fillMaxWidth().height(200.dp).padding(vertical = 4.dp))
        }
        OutlinedButton(
            onClick = {
                val uri = PhotoUtils.newCameraUri(context)
                pendingUri = uri.toString()
                camera.launch(uri)
            },
            modifier = Modifier.fillMaxWidth(),
            shape = RoundedCornerShape(12.dp),
            border = BorderStroke(1.dp, Slate200),
        ) { Text(if (fotoPath == null) "Ambil foto bukti (wajib)" else "Ambil ulang foto") }

        error?.let { Text(it, color = Red, fontSize = 13.sp) }

        PrimaryButton("Kirim laporan", loading = sending) {
            val beratKg = berat.replace(',', '.').toDoubleOrNull()
            val path = fotoPath
            error = when {
                titikId == null -> "Pilih lokasi Biopori."
                jenis.isBlank() -> "Isi jenis sampah."
                beratKg == null || beratKg <= 0 || beratKg > 100 -> "Berat harus antara 0 dan 100 kg."
                path == null -> "Foto bukti wajib diambil."
                else -> null
            }
            if (error != null || path == null || beratKg == null) return@PrimaryButton

            sending = true
            scope.launch {
                try {
                    val text = "text/plain".toMediaType()
                    // Waktu dikirim dalam UTC (ISO-8601) agar konsisten dengan zona waktu server.
                    val utc = OffsetDateTime.parse(waktu).withOffsetSameInstant(ZoneOffset.UTC).format(DateTimeFormatter.ISO_OFFSET_DATE_TIME)
                    val file = File(path)
                    client.api.laporBiopori(
                        titikId = titikId.toString().toRequestBody(text),
                        tanggal = utc.toRequestBody(text),
                        jenisSampah = jenis.trim().toRequestBody(text),
                        beratKg = String.format(java.util.Locale.US, "%.2f", beratKg).toRequestBody(text),
                        catatan = catatan.trim().toRequestBody(text),
                        foto = MultipartBody.Part.createFormData("foto_bukti", file.name, file.asRequestBody("image/jpeg".toMediaType())),
                    )
                    PhotoUtils.cleanup(context)
                    onDone()
                } catch (e: Exception) {
                    error = client.errorMessage(e)
                } finally {
                    sending = false
                }
            }
        }
        TextButton(onClick = onCancel, modifier = Modifier.fillMaxWidth()) { Text("Batal") }
    }
}
