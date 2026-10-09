package id.ecowin.app

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Checkbox
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.RadioButton
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.launch

/**
 * Daftar Nasabah baru: (1) pilih akun Google, email diverifikasi server dari ID token;
 * (2) isi data diri. Akun berstatus "menunggu" sampai diverifikasi petugas Bank Sampah.
 * Tidak ada OTP, password, atau PIN.
 */
@Composable
fun RegisterScreen(
    googleAuth: GoogleAuth,
    client: ApiClient,
    onRegistered: (LoginResponse) -> Unit,
    onBack: () -> Unit,
) {
    val scope = rememberCoroutineScope()
    var idToken by remember { mutableStateOf<String?>(null) }
    var nama by rememberSaveable { mutableStateOf("") }
    var noHp by rememberSaveable { mutableStateOf("") }
    var alamat by rememberSaveable { mutableStateOf("") }
    var bankId by rememberSaveable { mutableStateOf<Long?>(null) }
    var setuju by rememberSaveable { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }
    var busy by remember { mutableStateOf(false) }
    var banks by remember { mutableStateOf<List<BankPublikDto>>(emptyList()) }
    var banksError by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(Unit) {
        try {
            banks = client.api.bankSampahPublik().data
        } catch (e: Exception) {
            banksError = client.errorMessage(e)
        }
    }

    Column(
        Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(24.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        TextButton(onClick = onBack) { Text("← Kembali") }
        Text("Daftar akun baru", fontSize = 26.sp, fontWeight = FontWeight.ExtraBold, color = Emerald)
        Text("Akun Anda akan diperiksa petugas Bank Sampah sebelum bisa menarik saldo.", color = Slate500, fontSize = 14.sp)

        if (idToken == null) {
            Text("1. Pilih akun Google", fontWeight = FontWeight.Bold)
            PrimaryButton("Lanjutkan dengan Google", loading = busy) {
                busy = true
                error = null
                scope.launch {
                    when (val r = googleAuth.signIn()) {
                        is GoogleAuth.Result.Success -> idToken = r.idToken
                        is GoogleAuth.Result.Failure -> error = r.message
                        GoogleAuth.Result.Cancelled -> Unit
                    }
                    busy = false
                }
            }
        } else {
            Text("2. Data diri", fontWeight = FontWeight.Bold)
            OutlinedTextField(nama, { nama = it.take(150) }, label = { Text("Nama lengkap") }, singleLine = true, modifier = Modifier.fillMaxWidth())
            OutlinedTextField(
                noHp, { noHp = it.filter { c -> c.isDigit() || c == '+' }.take(16) },
                label = { Text("Nomor HP") }, placeholder = { Text("081234567890") }, singleLine = true,
                keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone), modifier = Modifier.fillMaxWidth(),
            )
            OutlinedTextField(alamat, { alamat = it.take(255) }, label = { Text("Alamat (jalan, RT/RW)") }, modifier = Modifier.fillMaxWidth())

            Text("Bank Sampah", color = Slate500, fontSize = 13.sp)
            banksError?.let { Text(it, color = Red, fontSize = 13.sp) }
            if (banks.isEmpty() && banksError == null) Text("Memuat…", color = Slate500, fontSize = 13.sp)
            banks.forEach { b ->
                Row(Modifier.fillMaxWidth().clickable { bankId = b.id }, verticalAlignment = Alignment.CenterVertically) {
                    RadioButton(selected = bankId == b.id, onClick = { bankId = b.id })
                    Text("${b.nama ?: "Bank Sampah"}${if (b.rt != null) " · RT ${b.rt}/RW ${b.rw}" else ""}")
                }
            }

            Row(Modifier.fillMaxWidth().clickable { setuju = !setuju }, verticalAlignment = Alignment.CenterVertically) {
                Checkbox(checked = setuju, onCheckedChange = { setuju = it })
                Text("Data benar dan saya bersedia diverifikasi petugas Bank Sampah.", fontSize = 13.sp)
            }

            PrimaryButton("Daftar sekarang", loading = busy) {
                error = when {
                    nama.trim().length < 3 -> "Isi nama lengkap."
                    noHp.length < 9 -> "Nomor HP tidak valid."
                    alamat.trim().length < 5 -> "Isi alamat."
                    bankId == null -> "Pilih Bank Sampah."
                    !setuju -> "Anda harus menyetujui pernyataan."
                    else -> null
                }
                val token = idToken
                val bank = bankId
                if (error != null || token == null || bank == null) return@PrimaryButton

                busy = true
                scope.launch {
                    try {
                        val response = client.api.register(
                            RegisterRequest(token, android.os.Build.MODEL ?: "android", nama.trim(), noHp, alamat.trim(), bank, true),
                        )
                        onRegistered(response)
                    } catch (e: Exception) {
                        error = client.errorMessage(e)
                    } finally {
                        busy = false
                    }
                }
            }
        }

        error?.let { Text(it, color = Red, fontSize = 14.sp, textAlign = TextAlign.Start) }
    }
}
