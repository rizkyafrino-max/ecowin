package id.ecowin.app

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.statusBarsPadding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.Text
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
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.launch

/**
 * Daftar Nasabah baru: (1) pilih akun Google, email diverifikasi server dari ID token;
 * (2) isi data diri. Akun berstatus "menunggu" sampai diverifikasi petugas Bank Sampah.
 * Tidak ada OTP, password, atau PIN. Tampilan mengikuti halaman daftar di Web.
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
    var jenisKelamin by rememberSaveable { mutableStateOf("") }
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

    fun kirim() {
        error = when {
            nama.trim().length < 3 -> "Isi nama lengkap."
            noHp.length < 9 -> "Nomor HP tidak valid."
            alamat.trim().length < 5 -> "Isi alamat."
            jenisKelamin.isEmpty() -> "Pilih jenis kelamin."
            bankId == null -> "Pilih Bank Sampah."
            !setuju -> "Anda harus menyetujui pernyataan."
            else -> null
        }
        val token = idToken
        val bank = bankId
        if (error != null || token == null || bank == null) return
        busy = true
        scope.launch {
            try {
                val response = client.api.register(
                    RegisterRequest(token, android.os.Build.MODEL ?: "android", nama.trim(), noHp, alamat.trim(), jenisKelamin, bank, true),
                )
                onRegistered(response)
            } catch (e: Exception) {
                error = client.errorMessage(e)
            } finally {
                busy = false
            }
        }
    }

    Column(Modifier.fillMaxSize().background(Color(0xFFECFDF5)).statusBarsPadding().imePadding()) {
        // Bilah atas: kembali + judul + langkah
        Row(Modifier.fillMaxWidth().padding(horizontal = 12.dp, vertical = 8.dp), verticalAlignment = Alignment.CenterVertically) {
            Box(
                Modifier.size(44.dp).clip(CircleShape).clickable(onClick = onBack),
                contentAlignment = Alignment.Center,
            ) { Icon(Lucide.ArrowLeft, contentDescription = "Kembali", tint = Slate900, modifier = Modifier.size(22.dp)) }
            Text("Daftar nasabah", fontWeight = FontWeight.ExtraBold, fontSize = 18.sp, color = Slate900, modifier = Modifier.padding(start = 4.dp))
        }
        Stepper(aktif = if (idToken == null) 1 else 2, modifier = Modifier.padding(horizontal = 24.dp, vertical = 6.dp))

        Column(
            Modifier.weight(1f).verticalScroll(rememberScrollState()).padding(horizontal = 16.dp, vertical = 12.dp),
            verticalArrangement = Arrangement.spacedBy(14.dp),
        ) {
            if (idToken == null) {
                LangkahGoogle(busy = busy, error = error) {
                    busy = true
                    error = null
                    scope.launch {
                        when (val r = googleAuth.signIn()) {
                            is GoogleAuth.Result.Success -> idToken = r.idToken
                            is GoogleAuth.Result.Failure -> error = r.message
                            GoogleAuth.Result.Cancelled -> error = "Pemilihan akun Google ditutup. Jika Anda tidak membatalkannya, Google belum mengenali aplikasi ini: buat OAuth client tipe Android (package id.ecowin.app + SHA-1 kunci debug) di Google Cloud, dan pastikan email Anda terdaftar sebagai Test user."
                        }
                        busy = false
                    }
                }
            } else {
                Row(
                    Modifier.fillMaxWidth().background(Color.White, RoundedCornerShape(16.dp)).border(1.dp, Slate200, RoundedCornerShape(16.dp)).padding(12.dp),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(10.dp),
                ) {
                    Icon(Lucide.ShieldCheck, contentDescription = null, tint = Emerald, modifier = Modifier.size(22.dp))
                    Column {
                        Text("Akun Google terverifikasi", fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                        Text("Lengkapi data diri di bawah ini.", color = Slate500, fontSize = 12.sp)
                    }
                }

                Column(
                    Modifier.fillMaxWidth().background(Color.White, RoundedCornerShape(20.dp)).border(1.dp, Slate200, RoundedCornerShape(20.dp)).padding(20.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp),
                ) {
                    EcoTextField(nama, { nama = it.take(150) }, label = { Text("Nama lengkap") }, singleLine = true, shape = RoundedCornerShape(14.dp), modifier = Modifier.fillMaxWidth())
                    EcoTextField(
                        noHp, { noHp = it.filter { c -> c.isDigit() || c == '+' }.take(16) },
                        label = { Text("Nomor HP") }, placeholder = { Text("081234567890") }, singleLine = true,
                        supportingText = { Text("Nomor aktif agar petugas dapat menghubungi Anda.") },
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Phone), shape = RoundedCornerShape(14.dp), modifier = Modifier.fillMaxWidth(),
                    )
                    EcoTextField(
                        alamat, { alamat = it.take(255) }, label = { Text("Alamat (jalan, RT/RW)") },
                        placeholder = { Text("Jl. Melati No. 7, RT 01/RW 05") }, minLines = 2, shape = RoundedCornerShape(14.dp), modifier = Modifier.fillMaxWidth(),
                    )
                    PilihJenisKelamin(jenisKelamin.ifEmpty { null }, { jenisKelamin = it })
                }

                Text("Pilih Bank Sampah", fontWeight = FontWeight.Bold, fontSize = 15.sp, color = Slate900)
                banksError?.let { Text(it, color = Red, fontSize = 13.sp) }
                if (banks.isEmpty() && banksError == null) {
                    Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        CircularProgressIndicator(Modifier.size(16.dp), strokeWidth = 2.dp, color = Emerald)
                        Text("Memuat daftar Bank Sampah…", color = Slate500, fontSize = 13.sp)
                    }
                }
                banks.forEach { b -> BankOption(b, selected = bankId == b.id) { bankId = b.id } }

                Row(
                    Modifier.fillMaxWidth().clip(RoundedCornerShape(14.dp)).clickable { setuju = !setuju }.padding(vertical = 6.dp),
                    verticalAlignment = Alignment.Top,
                    horizontalArrangement = Arrangement.spacedBy(12.dp),
                ) {
                    Box(
                        Modifier.size(24.dp).clip(RoundedCornerShape(7.dp))
                            .background(if (setuju) Emerald else Color.White)
                            .border(BorderStroke(1.5.dp, if (setuju) Emerald else Slate200), RoundedCornerShape(7.dp)),
                        contentAlignment = Alignment.Center,
                    ) { if (setuju) Icon(Lucide.Check, contentDescription = null, tint = Color.White, modifier = Modifier.size(16.dp)) }
                    Text("Saya menyatakan data di atas benar dan bersedia diverifikasi oleh petugas Bank Sampah.", fontSize = 13.sp, color = Slate500, modifier = Modifier.weight(1f))
                }

                error?.let {
                    Text(
                        it, color = Red, fontSize = 13.sp,
                        modifier = Modifier.fillMaxWidth().background(Color(0xFFFEF2F2), RoundedCornerShape(12.dp)).padding(12.dp),
                    )
                }
            }
            Box(Modifier.height(8.dp))
        }

        // Tombol tetap di bawah supaya selalu terjangkau (tidak tertutup keyboard/gulir panjang).
        if (idToken != null) {
            Column(
                Modifier.fillMaxWidth().background(Color.White).border(1.dp, Slate200).padding(horizontal = 16.dp, vertical = 12.dp).navigationBarsPadding(),
            ) {
                PrimaryButton("Daftar sekarang", loading = busy) { kirim() }
            }
        }
    }
}

@Composable
private fun LangkahGoogle(busy: Boolean, error: String?, onClick: () -> Unit) {
    Column(
        Modifier.fillMaxWidth().background(Color.White, RoundedCornerShape(24.dp)).border(1.dp, Slate200, RoundedCornerShape(24.dp)).padding(20.dp),
        verticalArrangement = Arrangement.spacedBy(14.dp),
    ) {
        Image(
            painterResource(R.drawable.ill_ecowin), contentDescription = null,
            modifier = Modifier.fillMaxWidth().height(140.dp),
        )
        Text("Buat akun EcoWin", fontWeight = FontWeight.ExtraBold, fontSize = 22.sp, color = Slate900)
        Text("Daftar sebagai nasabah Bank Sampah RT/RW Anda dengan akun Google. Akun diperiksa petugas sebelum aktif sepenuhnya.", color = Slate500, fontSize = 14.sp)

        Row(
            Modifier.fillMaxWidth().height(56.dp).clip(RoundedCornerShape(14.dp))
                .border(1.dp, Slate200, RoundedCornerShape(14.dp))
                .clickable(enabled = !busy, onClick = onClick).padding(horizontal = 16.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.Center,
        ) {
            if (busy) CircularProgressIndicator(Modifier.size(22.dp), strokeWidth = 2.dp, color = Emerald)
            else Image(painterResource(R.drawable.ic_google), contentDescription = null, modifier = Modifier.size(22.dp))
            Text(if (busy) "Membuka Google…" else "Daftar dengan Google", modifier = Modifier.padding(start = 12.dp), fontWeight = FontWeight.SemiBold, color = Slate900, fontSize = 16.sp)
        }

        Row(horizontalArrangement = Arrangement.spacedBy(8.dp), verticalAlignment = Alignment.Top) {
            Icon(Lucide.ShieldCheck, contentDescription = null, tint = Emerald, modifier = Modifier.size(16.dp).padding(top = 2.dp))
            Text("Kami hanya membaca nama dan email dari akun Google Anda. Tidak ada kata sandi yang dibuat.", color = Slate500, fontSize = 13.sp)
        }
        error?.let {
            Text(it, color = Red, fontSize = 13.sp, modifier = Modifier.fillMaxWidth().background(Color(0xFFFEF2F2), RoundedCornerShape(12.dp)).padding(12.dp))
        }
    }
}

/** Satu pilihan Bank Sampah berbentuk kartu (area sentuh besar, status terpilih jelas). */
@Composable
private fun BankOption(b: BankPublikDto, selected: Boolean, onClick: () -> Unit) {
    val shape = RoundedCornerShape(16.dp)
    Row(
        Modifier.fillMaxWidth().clip(shape)
            .background(if (selected) EmeraldSoft else Color.White)
            .border(if (selected) 2.dp else 1.dp, if (selected) Emerald else Slate200, shape)
            .clickable(onClick = onClick).padding(14.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        Box(Modifier.size(40.dp).background(if (selected) Color.White else EmeraldSoft, RoundedCornerShape(12.dp)), contentAlignment = Alignment.Center) {
            Icon(Lucide.Building2, contentDescription = null, tint = Emerald, modifier = Modifier.size(20.dp))
        }
        Column(Modifier.weight(1f)) {
            Text(b.nama ?: "Bank Sampah", fontWeight = FontWeight.SemiBold, fontSize = 15.sp, color = Slate900)
            if (b.rt != null) Text("RT ${b.rt} / RW ${b.rw}", color = Slate500, fontSize = 12.sp)
        }
        if (selected) Icon(Lucide.Check, contentDescription = "Dipilih", tint = Emerald, modifier = Modifier.size(20.dp))
    }
}

/** Indikator langkah: 1 Akun Google, 2 Data diri, 3 Petugas (sama dengan halaman daftar Web). */
@Composable
private fun Stepper(aktif: Int, modifier: Modifier = Modifier) {
    val label = listOf("Akun Google", "Data diri", "Petugas")
    Row(modifier.fillMaxWidth(), verticalAlignment = Alignment.Top) {
        label.forEachIndexed { i, teks ->
            val nomor = i + 1
            val selesai = nomor < aktif
            val on = nomor == aktif
            Column(Modifier.weight(1f), horizontalAlignment = Alignment.CenterHorizontally) {
                Box(
                    Modifier.size(28.dp).clip(CircleShape).background(if (selesai || on) Emerald else Color.White)
                        .border(1.5.dp, if (selesai || on) Emerald else Slate200, CircleShape),
                    contentAlignment = Alignment.Center,
                ) {
                    if (selesai) Icon(Lucide.Check, contentDescription = null, tint = Color.White, modifier = Modifier.size(16.dp))
                    else Text(nomor.toString(), color = if (on) Color.White else Slate500, fontWeight = FontWeight.Bold, fontSize = 13.sp)
                }
                Text(teks, fontSize = 11.sp, fontWeight = if (on) FontWeight.Bold else FontWeight.Medium, color = if (on || selesai) Slate900 else Slate500, modifier = Modifier.padding(top = 4.dp))
            }
        }
    }
}
