package id.ecowin.app

import android.graphics.BitmapFactory
import android.util.Base64
import androidx.compose.foundation.Image
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.Icon
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
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

/** Profil: kartu data akun + formulir (seperti halaman Profil di Web), lalu QR Card Nasabah dan tombol keluar. */
@Composable
fun ProfilTab(client: ApiClient, user: UserDto, refreshKey: Int, onUserChanged: (UserDto) -> Unit, onLogout: () -> Unit) {
    val qr = rememberLoadable(client, refreshKey) { client.api.qr() }
    val scope = rememberCoroutineScope()
    val nasabah = user.nasabah

    var nama by remember(user) { mutableStateOf(user.nama.orEmpty()) }
    var noHp by remember(user) { mutableStateOf(nasabah?.noHp.orEmpty()) }
    var alamat by remember(user) { mutableStateOf(nasabah?.alamatRtRw.orEmpty()) }
    var message by remember { mutableStateOf<String?>(null) }
    var ok by remember { mutableStateOf(false) }
    var saving by remember { mutableStateOf(false) }

    TabList {
        item {
            EcoCard {
                Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(16.dp)) {
                    Avatar(user.nama, size = 64)
                    Column(Modifier.weight(1f)) {
                        Text(user.nama ?: "-", fontSize = 18.sp, fontWeight = FontWeight.Bold, maxLines = 1, overflow = TextOverflow.Ellipsis)
                        Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(6.dp)) {
                            Icon(Lucide.Mail, contentDescription = null, tint = Slate500, modifier = Modifier.size(14.dp))
                            Text(user.email ?: "", color = Slate500, fontSize = 13.sp, maxLines = 1, overflow = TextOverflow.Ellipsis)
                        }
                        Text(listOfNotNull(nasabah?.nomorNasabah, nasabah?.bankSampah?.nama).joinToString(" · "), color = Slate500, fontSize = 12.sp)
                    }
                }

                Column(Modifier.padding(top = 12.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    OutlinedTextField(nama, { nama = it.take(150); ok = false }, label = { Text("Nama lengkap") }, singleLine = true, modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(14.dp))
                    OutlinedTextField(
                        noHp, { noHp = it.filter { c -> c.isDigit() || c == '+' }.take(15); ok = false },
                        label = { Text("Nomor HP") }, placeholder = { Text("08xxxxxxxxxx") }, singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone), modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(14.dp),
                    )
                    OutlinedTextField(alamat, { alamat = it.take(255); ok = false }, label = { Text("Alamat (RT/RW)") }, modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(14.dp))
                    OutlinedTextField(
                        user.email.orEmpty(), {}, label = { Text("Email Google") }, singleLine = true, enabled = false,
                        supportingText = { Text("Email tidak dapat diubah. Hubungi petugas Bank Sampah bila perlu.") },
                        modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(14.dp),
                    )
                    message?.let { Text(it, fontSize = 13.sp, color = if (ok) Emerald else Red, fontWeight = FontWeight.SemiBold) }
                    PrimaryButton("Simpan perubahan", loading = saving) {
                        saving = true
                        scope.launch {
                            try {
                                val updated = client.api.updateProfile(ProfileUpdateRequest(nama.trim().ifBlank { null }, noHp.trim(), alamat.trim())).data
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
            EcoCard {
                LoadableContent(qr) { q ->
                    val bitmap = remember(q.qrPngBase64) {
                        runCatching {
                            val bytes = Base64.decode(q.qrPngBase64, Base64.DEFAULT)
                            BitmapFactory.decodeByteArray(bytes, 0, bytes.size)?.asImageBitmap()
                        }.getOrNull()
                    }
                    Column(Modifier.fillMaxWidth(), horizontalAlignment = Alignment.CenterHorizontally, verticalArrangement = Arrangement.spacedBy(6.dp)) {
                        Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                            Icon(Lucide.QrCode, contentDescription = null, tint = Emerald, modifier = Modifier.size(16.dp))
                            Text("QR Card Nasabah", color = Emerald, fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                        }
                        bitmap?.let {
                            Image(it, contentDescription = "QR Card nasabah", modifier = Modifier.padding(top = 8.dp).size(208.dp).border(1.dp, Slate200, RoundedCornerShape(16.dp)).padding(8.dp))
                        }
                        Text(q.nama ?: user.nama ?: "", fontSize = 18.sp, fontWeight = FontWeight.Bold, modifier = Modifier.padding(top = 8.dp))
                        Text(q.nomorNasabah ?: "", color = Slate500, fontFamily = FontFamily.Monospace, letterSpacing = 1.sp)
                        Text(
                            "Tunjukkan QR ini kepada petugas Bank Sampah. QR hanya berisi kode acak, bukan data pribadi.",
                            color = Slate500, fontSize = 12.sp, textAlign = TextAlign.Center, modifier = Modifier.padding(top = 4.dp),
                        )
                    }
                }
            }
        }

        item {
            OutlinedButton(onClick = onLogout, modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(14.dp)) {
                Icon(Lucide.LogOut, contentDescription = null, tint = Red, modifier = Modifier.size(18.dp).padding(end = 0.dp))
                Text("  Keluar", color = Red, fontWeight = FontWeight.SemiBold)
            }
            Text(
                "Setelah keluar, Anda perlu masuk kembali dengan Google.",
                color = Slate500, fontSize = 12.sp, textAlign = TextAlign.Center,
                modifier = Modifier.fillMaxWidth().padding(top = 8.dp),
            )
        }
    }
}
