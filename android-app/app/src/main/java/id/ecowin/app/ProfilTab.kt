package id.ecowin.app

import android.graphics.BitmapFactory
import android.util.Base64
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.offset
import androidx.compose.ui.draw.clip
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.ui.graphics.Color
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.RequestBody.Companion.asRequestBody
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.ExperimentalLayoutApi
import androidx.compose.foundation.layout.FlowRow
import androidx.compose.ui.graphics.Brush
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.Icon
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.launch

/**
 * Profil: kepala hijau (foto, nama, lencana), kartu QR Nasabah, data diri yang bisa diubah, lalu daftar Akun (Pengaturan,
 * pembuat aplikasi) dan tombol keluar. Gaya daftarnya sama dengan Pengaturan.
 */
@OptIn(ExperimentalLayoutApi::class)
@Composable
fun ProfilTab(client: ApiClient, user: UserDto, refreshKey: Int, onUserChanged: (UserDto) -> Unit, onLogout: () -> Unit, go: (Tab) -> Unit) {
    val qr = rememberLoadable(client, refreshKey) { client.api.qr() }
    val scope = rememberCoroutineScope()
    val nasabah = user.nasabah

    var nama by remember(user) { mutableStateOf(user.nama.orEmpty()) }
    var noHp by remember(user) { mutableStateOf(nasabah?.noHp.orEmpty()) }
    var alamat by remember(user) { mutableStateOf(nasabah?.alamatRtRw.orEmpty()) }
    var message by remember { mutableStateOf<String?>(null) }
    var ok by remember { mutableStateOf(false) }
    var saving by remember { mutableStateOf(false) }
    var jenisKelamin by remember(user) { mutableStateOf(user.jenisKelamin) }
    var fotoBusy by remember { mutableStateOf(false) }
    var fotoPesan by remember { mutableStateOf<String?>(null) }
    val konteks = androidx.compose.ui.platform.LocalContext.current
    // Pilih foto dari galeri: diperkecil dan di-encode ulang di perangkat (tanpa metadata), lalu diunggah; server memeriksa dan menggambar ulang lagi.
    val pilihFoto = androidx.activity.compose.rememberLauncherForActivityResult(androidx.activity.result.contract.ActivityResultContracts.GetContent()) { uri ->
        if (uri != null) {
            fotoBusy = true
            fotoPesan = null
            scope.launch {
                try {
                    val berkas = kotlinx.coroutines.withContext(kotlinx.coroutines.Dispatchers.IO) { PhotoUtils.compress(konteks, uri) }
                    try {
                        val bagian = okhttp3.MultipartBody.Part.createFormData("foto", "foto.jpg", berkas.asRequestBody("image/jpeg".toMediaType()))
                        onUserChanged(client.api.gantiFoto(bagian).data)
                    } finally {
                        berkas.delete()
                    }
                } catch (e: Exception) {
                    fotoPesan = if (e is IllegalArgumentException) "Berkas bukan gambar yang valid." else client.errorMessage(e)
                } finally {
                    fotoBusy = false
                }
            }
        }
    }

    TabList {
        item {
            Box(
                Modifier.fillMaxWidth().clip(RoundedCornerShape(28.dp))
                    .background(Brush.linearGradient(listOf(Color(0xFF065F46), Emerald, Color(0xFF10B981)))),
            ) {
                Box(Modifier.align(Alignment.TopEnd).offset(x = 50.dp, y = (-50).dp).size(170.dp).background(Color.White.copy(alpha = 0.10f), CircleShape))
                Box(Modifier.align(Alignment.BottomStart).offset(x = (-40).dp, y = 50.dp).size(130.dp).background(Color.White.copy(alpha = 0.07f), CircleShape))
                Column(
                    Modifier.fillMaxWidth().padding(horizontal = 20.dp, vertical = 24.dp),
                    horizontalAlignment = Alignment.CenterHorizontally, verticalArrangement = Arrangement.spacedBy(6.dp),
                ) {
                    Box {
                        Box(Modifier.size(98.dp).background(Color.White, CircleShape).padding(4.dp)) {
                            Avatar(user.nama, size = 90, url = user.avatar, onClick = { if (!fotoBusy) pilihFoto.launch("image/*") })
                        }
                        Box(
                            Modifier.align(Alignment.BottomEnd).size(32.dp).background(EmeraldDeep, CircleShape).border(2.dp, Color.White, CircleShape)
                                .clickable(enabled = !fotoBusy, role = androidx.compose.ui.semantics.Role.Button, onClickLabel = "Ganti foto profil") { pilihFoto.launch("image/*") },
                            contentAlignment = Alignment.Center,
                        ) { Icon(Lucide.Camera, contentDescription = "Ganti foto profil", tint = Color.White, modifier = Modifier.size(16.dp)) }
                    }
                    Text(
                        user.nama ?: "-", color = Color.White, fontSize = 22.sp, fontWeight = FontWeight.ExtraBold, textAlign = TextAlign.Center,
                        maxLines = 2, overflow = TextOverflow.Ellipsis, modifier = Modifier.padding(top = 6.dp),
                    )
                    Text(user.email ?: "", color = Color(0xFFD1FAE5), fontSize = 13.sp, maxLines = 1, overflow = TextOverflow.Ellipsis)

                    FlowRow(
                        Modifier.padding(top = 4.dp), horizontalArrangement = Arrangement.spacedBy(8.dp, Alignment.CenterHorizontally),
                        verticalArrangement = Arrangement.spacedBy(8.dp),
                    ) {
                        nasabah?.nomorNasabah?.let { LencanaProfil(it, mono = true) }
                        nasabah?.bankSampah?.nama?.let { LencanaProfil(it) }
                        if (nasabah?.statusVerifikasi == "verified") LencanaProfil("Terverifikasi", ikon = Lucide.ShieldCheck)
                    }

                    Row(Modifier.padding(top = 8.dp), horizontalArrangement = Arrangement.spacedBy(8.dp), verticalAlignment = Alignment.CenterVertically) {
                        Text(
                            if (fotoBusy) "Mengunggah…" else "Ganti foto", color = EmeraldDeep, fontWeight = FontWeight.Bold, fontSize = 13.sp,
                            modifier = Modifier.background(Color.White, RoundedCornerShape(50)).clickable(enabled = !fotoBusy) { pilihFoto.launch("image/*") }.padding(horizontal = 16.dp, vertical = 8.dp),
                        )
                        if (user.punyaFoto) {
                            Text(
                                "Hapus foto", color = Color.White, fontWeight = FontWeight.SemiBold, fontSize = 13.sp,
                                modifier = Modifier.background(Color.White.copy(alpha = 0.20f), RoundedCornerShape(50)).clickable(enabled = !fotoBusy) {
                                    fotoBusy = true
                                    fotoPesan = null
                                    scope.launch {
                                        try { onUserChanged(client.api.hapusFoto().data) } catch (e: Exception) { fotoPesan = client.errorMessage(e) } finally { fotoBusy = false }
                                    }
                                }.padding(horizontal = 16.dp, vertical = 8.dp),
                            )
                        }
                    }
                    fotoPesan?.let { Text(it, color = Color(0xFFFECACA), fontSize = 12.sp, fontWeight = FontWeight.SemiBold, textAlign = TextAlign.Center) }
                }
            }
        }

        item {
            EcoCard {
                LoadableContent(qr) { q ->
                    val bitmap = remember(q.qrPngBase64) {
                        runCatching {
                            val bytes = Base64.decode(q.qrPngBase64, Base64.DEFAULT)
                            BitmapFactory.decodeByteArray(bytes, 0, bytes.size)?.asImageBitmap()
                        }.getOrNull()
                    }
                    Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(16.dp)) {
                        bitmap?.let {
                            Image(it, contentDescription = "QR Card nasabah", modifier = Modifier.size(132.dp).border(1.dp, Slate200, RoundedCornerShape(16.dp)).padding(8.dp))
                        }
                        Column(Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                            Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                                Icon(Lucide.QrCode, contentDescription = null, tint = Emerald, modifier = Modifier.size(16.dp))
                                Text("QR CARD NASABAH", color = Emerald, fontWeight = FontWeight.Bold, fontSize = 11.sp, letterSpacing = 1.sp)
                            }
                            Text(q.nama ?: user.nama ?: "", fontSize = 17.sp, fontWeight = FontWeight.Bold, color = Slate900, maxLines = 2, overflow = TextOverflow.Ellipsis)
                            Text(q.nomorNasabah ?: "", color = Slate500, fontFamily = FontFamily.Monospace, letterSpacing = 1.sp, fontSize = 13.sp)
                            Text(
                                "Tunjukkan QR ini kepada petugas. QR hanya berisi kode acak, bukan data pribadi.",
                                color = Slate500, fontSize = 12.sp, lineHeight = 17.sp, modifier = Modifier.padding(top = 2.dp),
                            )
                        }
                    }
                }
            }
        }

        item {
            Kelompok("Data diri") {
                Column(Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    EcoTextField(nama, { nama = it.take(150); ok = false }, label = { Text("Nama lengkap") }, singleLine = true, modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(14.dp))
                    PilihJenisKelamin(jenisKelamin, { jenisKelamin = it; ok = false })
                    EcoTextField(
                        noHp, { noHp = it.filter { c -> c.isDigit() || c == '+' }.take(15); ok = false },
                        label = { Text("Nomor HP") }, placeholder = { Text("08xxxxxxxxxx") }, singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone), modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(14.dp),
                    )
                    EcoTextField(alamat, { alamat = it.take(255); ok = false }, label = { Text("Alamat (RT/RW)") }, modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(14.dp))
                    EcoTextField(
                        user.email.orEmpty(), {}, label = { Text("Email Google") }, singleLine = true, enabled = false,
                        supportingText = { Text("Email tidak dapat diubah. Hubungi petugas Bank Sampah bila perlu.") },
                        modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(14.dp),
                    )
                    message?.let { Text(it, fontSize = 13.sp, color = if (ok) Emerald else Red, fontWeight = FontWeight.SemiBold) }
                    PrimaryButton("Simpan perubahan", loading = saving) {
                        saving = true
                        scope.launch {
                            try {
                                val updated = client.api.updateProfile(ProfileUpdateRequest(nama.trim().ifBlank { null }, noHp.trim(), alamat.trim(), jenisKelamin)).data
                                onUserChanged(updated)
                                ok = true
                                message = "Profil diperbarui."
                            } catch (e: Exception) {
                                ok = false
                                message = client.errorMessage(e)
                            } finally {
                                saving = false
                            }
                        }
                    }
                }
            }
        }

        item {
            val situs = androidx.compose.ui.platform.LocalUriHandler.current
            Kelompok("Akun") {
                Baris(Lucide.Settings, "Pengaturan", "Pemberitahuan, nada, dan lapor bug", warnaIkon = Slate500, latarIkon = LatarNetral, onClick = { go(Tab.Pengaturan) }) { PanahBaris() }
                Pemisah()
                Baris(Lucide.UserRound, "Dibuat oleh Rizky", "rizky.pplgku.my.id", onClick = { runCatching { situs.openUri(URL_PEMBUAT) } }) { TautanLuar() }
            }
        }

        item {
            androidx.compose.material3.Button(
                onClick = onLogout, modifier = Modifier.fillMaxWidth().height(52.dp), shape = RoundedCornerShape(50),
                colors = androidx.compose.material3.ButtonDefaults.buttonColors(containerColor = Red, contentColor = Color.White),
            ) {
                Icon(Lucide.LogOut, contentDescription = null, tint = Color.White, modifier = Modifier.size(18.dp))
                Text("  Keluar", color = Color.White, fontWeight = FontWeight.SemiBold)
            }
            Text(
                "Setelah keluar, Anda perlu masuk kembali dengan Google.",
                color = Slate500, fontSize = 12.sp, textAlign = TextAlign.Center,
                modifier = Modifier.fillMaxWidth().padding(top = 8.dp),
            )
        }
    }
}

/** Lencana kecil bening di atas kepala hijau profil. */
@Composable
private fun LencanaProfil(teks: String, mono: Boolean = false, ikon: androidx.compose.ui.graphics.vector.ImageVector? = null) {
    Row(
        Modifier.background(Color.White.copy(alpha = 0.20f), RoundedCornerShape(50)).padding(horizontal = 12.dp, vertical = 6.dp),
        verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(6.dp),
    ) {
        if (ikon != null) Icon(ikon, contentDescription = null, tint = Color.White, modifier = Modifier.size(14.dp))
        Text(teks, color = Color.White, fontSize = 12.sp, fontWeight = FontWeight.SemiBold, fontFamily = if (mono) FontFamily.Monospace else FontFamily.Default, maxLines = 1, overflow = TextOverflow.Ellipsis)
    }
}
