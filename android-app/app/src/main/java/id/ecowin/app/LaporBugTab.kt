package id.ecowin.app

import android.graphics.BitmapFactory
import android.os.Build
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Icon
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.ImageBitmap
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
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

private const val BATAS_TEKS = 1500
private const val MINIMAL_TEKS = 10

/** Informasi teknis yang ikut terkirim (tanpa data pribadi); pengguna diberi tahu bahwa info teknis dikirim, tanpa rinciannya. */
internal fun infoPerangkat(layarAsal: String): String =
    "Layar: $layarAsal\n" +
        "EcoWin ${BuildConfig.VERSION_NAME} • Android ${Build.VERSION.RELEASE} (API ${Build.VERSION.SDK_INT}) • ${Build.MANUFACTURER} ${Build.MODEL}"

/** Isi laporan yang dikirim: teks pengguna (dipotong dan dirapikan) diikuti info teknis. Tetap di bawah batas 2000 karakter server. */
internal fun susunLaporan(teks: String, info: String): String =
    teks.trim().take(BATAS_TEKS) + "\n\n— Info otomatis —\n" + info

/** Halaman lapor bug: dibuka dari menu Pengaturan atau dengan mengguncang HP. Laporan masuk ke panel admin (kategori "bug"). */
@Composable
fun LaporBugTab(client: ApiClient, layarAsal: String, onKembali: () -> Unit) {
    val konteks = LocalContext.current
    var teks by rememberSaveable { mutableStateOf("") }
    var terkirim by rememberSaveable { mutableStateOf(false) }
    var mengirim by remember { mutableStateOf(false) }
    var menyiapkan by remember { mutableStateOf(false) }
    var galat by remember { mutableStateOf<String?>(null) }
    var berkas by remember { mutableStateOf<File?>(null) }
    var pratinjau by remember { mutableStateOf<ImageBitmap?>(null) }
    val scope = rememberCoroutineScope()
    val info = remember(layarAsal) { infoPerangkat(layarAsal) }
    val cukup = teks.trim().length >= MINIMAL_TEKS

    fun buangFoto() { berkas?.delete(); berkas = null; pratinjau = null }

    // Foto dipilih dari galeri lalu diperkecil dan di-encode ulang di HP (metadata dan lokasi terbuang); server memeriksa dan menggambar ulang lagi.
    val pilihFoto = androidx.activity.compose.rememberLauncherForActivityResult(androidx.activity.result.contract.ActivityResultContracts.GetContent()) { uri ->
        if (uri != null) {
            menyiapkan = true
            galat = null
            scope.launch {
                try {
                    val hasil = withContext(Dispatchers.IO) { PhotoUtils.compress(konteks, uri) }
                    val gambar = withContext(Dispatchers.IO) {
                        BitmapFactory.decodeFile(hasil.path, BitmapFactory.Options().apply { inSampleSize = 2 })?.asImageBitmap()
                    }
                    buangFoto()
                    berkas = hasil
                    pratinjau = gambar
                } catch (e: Exception) {
                    galat = if (e is IllegalArgumentException) "Berkas bukan gambar yang valid." else "Foto tidak dapat dibaca. Coba foto lain."
                } finally {
                    menyiapkan = false
                }
            }
        }
    }

    TabList {
        if (terkirim) {
            item {
                EcoCard {
                    Column(Modifier.fillMaxWidth(), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        Icon(Lucide.ShieldCheck, contentDescription = null, tint = Emerald, modifier = Modifier.size(28.dp))
                        Text("Terima kasih, laporan terkirim", fontWeight = FontWeight.Bold, fontSize = 17.sp, color = Slate900)
                        Text("Laporan Anda sudah masuk ke tim EcoWin dan akan kami periksa.", color = Slate500, fontSize = 14.sp)
                        Column(Modifier.padding(top = 8.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
                            PrimaryButton("Kembali") { onKembali() }
                            OutlinedButton(
                                onClick = { teks = ""; terkirim = false },
                                modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(50),
                            ) { Text("Kirim laporan lain", color = Slate900, fontWeight = FontWeight.SemiBold) }
                        }
                    }
                }
            }
        } else {
            item {
                EcoCard {
                    Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                        Text("Ceritakan masalah yang Anda temui: apa yang Anda lakukan, apa yang terjadi, dan apa yang seharusnya terjadi.", color = Slate500, fontSize = 14.sp)
                        EcoTextField(
                            teks, { teks = it.take(BATAS_TEKS); galat = null },
                            label = { Text("Apa masalahnya?") },
                            placeholder = { Text("Contoh: saat menekan Tarik Saldo, layar menjadi putih.") },
                            minLines = 5, modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(14.dp),
                            supportingText = { Text(if (cukup) "${teks.length}/$BATAS_TEKS" else "Minimal $MINIMAL_TEKS karakter. ${teks.length}/$BATAS_TEKS") },
                        )

                        Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                            Text("Foto bukti (opsional)", fontWeight = FontWeight.SemiBold, fontSize = 13.sp, color = Slate900)
                            val gambar = pratinjau
                            if (gambar == null) {
                                OutlinedButton(
                                    onClick = { pilihFoto.launch("image/*") }, enabled = !menyiapkan && !mengirim,
                                    modifier = Modifier.fillMaxWidth().heightIn(min = 52.dp), shape = RoundedCornerShape(14.dp),
                                ) {
                                    Icon(Lucide.ImagePlus, contentDescription = null, tint = Emerald, modifier = Modifier.size(20.dp))
                                    Text(if (menyiapkan) "  Menyiapkan foto…" else "  Tambah foto atau tangkapan layar", color = Slate900, fontWeight = FontWeight.SemiBold)
                                }
                            } else {
                                Box(
                                    Modifier.fillMaxWidth().clip(RoundedCornerShape(16.dp)).background(Color(0xFFF1F5F9)).border(1.dp, Slate200, RoundedCornerShape(16.dp)),
                                ) {
                                    Image(
                                        gambar, contentDescription = "Foto bukti laporan", contentScale = ContentScale.Fit,
                                        modifier = Modifier.fillMaxWidth().heightIn(max = 240.dp),
                                    )
                                    Box(
                                        Modifier.align(Alignment.TopEnd).padding(8.dp).size(36.dp).background(Color(0x99000000), CircleShape)
                                            .clickable(enabled = !mengirim, role = androidx.compose.ui.semantics.Role.Button, onClickLabel = "Hapus foto") { buangFoto() },
                                        contentAlignment = Alignment.Center,
                                    ) { Icon(Lucide.X, contentDescription = "Hapus foto", tint = Color.White, modifier = Modifier.size(18.dp)) }
                                }
                            }
                            Text("Foto membantu kami melihat masalahnya. Hindari menampilkan data pribadi Anda; lokasi dan metadata foto dihapus otomatis.", color = Slate500, fontSize = 12.sp)
                        }

                        Text("Info teknis aplikasi (versi aplikasi dan jenis HP) ikut terkirim untuk membantu kami memperbaiki masalah. Tidak ada data pribadi atau saldo yang dikirim.", color = Slate500, fontSize = 12.sp)
                        galat?.let { Text(it, color = Red, fontSize = 13.sp, fontWeight = FontWeight.SemiBold) }
                        PrimaryButton("Kirim laporan", enabled = cukup && !menyiapkan, loading = mengirim, icon = Lucide.Bug) {
                            mengirim = true
                            galat = null
                            scope.launch {
                                try {
                                    val teksBiasa = "text/plain; charset=utf-8".toMediaType()
                                    val bagianFoto = berkas?.let { MultipartBody.Part.createFormData("foto", "bukti.jpg", it.asRequestBody("image/jpeg".toMediaType())) }
                                    client.api.kirimLaporan("bug".toRequestBody(teksBiasa), susunLaporan(teks, info).toRequestBody(teksBiasa), bagianFoto)
                                    buangFoto()
                                    teks = ""
                                    terkirim = true
                                } catch (e: Exception) {
                                    galat = client.errorMessage(e)
                                } finally {
                                    mengirim = false
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
