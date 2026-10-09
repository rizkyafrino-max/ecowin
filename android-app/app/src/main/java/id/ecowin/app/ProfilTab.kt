package id.ecowin.app

import android.graphics.BitmapFactory
import android.util.Base64
import androidx.compose.foundation.Image
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
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
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.launch

@Composable
fun ProfilTab(client: ApiClient, user: UserDto, refreshKey: Int, onUserChanged: (UserDto) -> Unit, onLogout: () -> Unit) {
    val qr = rememberLoadable(client, refreshKey) { client.api.qr() }
    val scope = rememberCoroutineScope()
    val nasabah = user.nasabah

    var noHp by remember(user) { mutableStateOf(nasabah?.noHp.orEmpty()) }
    var alamat by remember(user) { mutableStateOf(nasabah?.alamatRtRw.orEmpty()) }
    var message by remember { mutableStateOf<String?>(null) }
    var saving by remember { mutableStateOf(false) }

    TabList {
        item {
            EcoCard {
                Text(nasabah?.nama ?: user.nama ?: "-", fontWeight = FontWeight.Bold, fontSize = 18.sp)
                Text(user.email ?: "", color = Slate500, fontSize = 13.sp)
                LabelValue("No. nasabah", nasabah?.nomorNasabah ?: "-")
                LabelValue("Bank Sampah", nasabah?.bankSampah?.nama ?: "-")
                LabelValue("RT / RW", "${nasabah?.bankSampah?.rt ?: "-"} / ${nasabah?.bankSampah?.rw ?: "-"}")
            }
        }

        item {
            EcoCard {
                Text("QR Card", fontWeight = FontWeight.Bold)
                Text("Tunjukkan QR ini kepada petugas saat menyetor sampah.", color = Slate500, fontSize = 13.sp)
                LoadableContent(qr) { q ->
                    val bitmap = remember(q.qrPngBase64) {
                        runCatching {
                            val bytes = Base64.decode(q.qrPngBase64, Base64.DEFAULT)
                            BitmapFactory.decodeByteArray(bytes, 0, bytes.size)?.asImageBitmap()
                        }.getOrNull()
                    }
                    Column(Modifier.fillMaxWidth(), horizontalAlignment = Alignment.CenterHorizontally) {
                        bitmap?.let { Image(it, contentDescription = "QR Card nasabah", modifier = Modifier.size(220.dp)) }
                        Text(q.nomorNasabah ?: "", fontWeight = FontWeight.SemiBold, textAlign = TextAlign.Center)
                    }
                }
            }
        }

        item {
            EcoCard {
                Text("Ubah data kontak", fontWeight = FontWeight.Bold)
                OutlinedTextField(
                    noHp, { noHp = it.filter { c -> c.isDigit() || c == '+' }.take(15) },
                    label = { Text("No. HP") }, singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone), modifier = Modifier.fillMaxWidth(),
                )
                OutlinedTextField(alamat, { alamat = it.take(255) }, label = { Text("Alamat RT/RW") }, modifier = Modifier.fillMaxWidth())
                message?.let { Text(it, fontSize = 13.sp, color = Slate500) }
                PrimaryButton("Simpan", loading = saving) {
                    saving = true
                    scope.launch {
                        try {
                            val updated = client.api.updateProfile(ProfileUpdateRequest(null, noHp.trim(), alamat.trim())).data
                            onUserChanged(updated)
                            message = "Data tersimpan."
                        } catch (e: Exception) {
                            message = client.errorMessage(e)
                        } finally {
                            saving = false
                        }
                    }
                }
            }
        }

        item {
            OutlinedButton(onClick = onLogout, modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(12.dp)) {
                Text("Keluar", color = Red)
            }
            Text(
                "Setelah keluar, Anda perlu masuk kembali dengan Google.",
                color = Slate500, fontSize = 12.sp, textAlign = TextAlign.Center,
                modifier = Modifier.fillMaxWidth(),
            )
        }
    }
}
