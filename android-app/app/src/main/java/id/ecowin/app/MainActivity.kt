package id.ecowin.app

import android.net.Uri
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.compose.setContent
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import kotlinx.coroutines.launch
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.toRequestBody
import java.time.LocalDate
import androidx.compose.ui.graphics.asImageBitmap
import android.graphics.Bitmap
import com.google.zxing.BarcodeFormat
import com.google.zxing.MultiFormatWriter
import com.google.zxing.common.BitMatrix

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent { EcoWinApp() }
    }
}

private enum class Screen { DASHBOARD, TRANSAKSI, BIOPORI, AKUN, KARTU, HARGA }

private fun qrBitmap(value: String): androidx.compose.ui.graphics.ImageBitmap {
    val matrix: BitMatrix = MultiFormatWriter().encode(value, BarcodeFormat.QR_CODE, 512, 512)
    val bitmap = Bitmap.createBitmap(512, 512, Bitmap.Config.ARGB_8888)
    for (x in 0 until 512) {
        for (y in 0 until 512) {
            bitmap.setPixel(x, y, if (matrix[x, y]) android.graphics.Color.BLACK else android.graphics.Color.WHITE)
        }
    }
    return bitmap.asImageBitmap()
}

@Composable
private fun EcoWinApp() {
    val context = LocalContext.current
    var token by remember { mutableStateOf(SessionStore.readToken(context)) }
    Surface(Modifier.fillMaxSize()) {
        if (token == null) {
            LoginScreen { newToken -> SessionStore.saveToken(context, newToken); token = newToken }
        } else {
            MainScreen(token = token!!, onLogout = { SessionStore.clear(context); token = null })
        }
    }
}

@Composable
private fun LoginScreen(onLoggedIn: (String) -> Unit) {
    var username by remember { mutableStateOf("") }
    var pin by remember { mutableStateOf("") }
    var message by remember { mutableStateOf<String?>(null) }
    var loading by remember { mutableStateOf(false) }
    val scope = rememberCoroutineScope()
    Column(Modifier.fillMaxSize().padding(24.dp), verticalArrangement = Arrangement.Center) {
        Text("EcoWin", style = MaterialTheme.typography.headlineLarge)
        Text("Masuk dengan username dan PIN dari petugas")
        Spacer(Modifier.height(20.dp))
        OutlinedTextField(username, { username = it }, label = { Text("Username") }, modifier = Modifier.fillMaxWidth(), singleLine = true)
        OutlinedTextField(pin, { pin = it }, label = { Text("PIN") }, modifier = Modifier.fillMaxWidth(), singleLine = true)
        Spacer(Modifier.height(12.dp))
        Button(enabled = !loading && username.isNotBlank() && pin.isNotBlank(), modifier = Modifier.fillMaxWidth(), onClick = {
            scope.launch {
                loading = true
                runCatching { ApiClient.api.login(LoginRequest(username.trim(), pin)).token }
                    .onSuccess(onLoggedIn).onFailure { message = "Login gagal. Periksa username, PIN, atau koneksi." }
                loading = false
            }
        }) { Text(if (loading) "Memuat..." else "Masuk") }
        message?.let { Text(it, color = MaterialTheme.colorScheme.error) }
    }
}

@Composable
private fun MainScreen(token: String, onLogout: () -> Unit) {
    var screen by remember { mutableStateOf(Screen.DASHBOARD) }
    Column(Modifier.fillMaxSize()) {
        Box(Modifier.weight(1f)) {
            when (screen) {
                Screen.DASHBOARD -> DashboardScreen(token)
                Screen.TRANSAKSI -> TransactionScreen(token)
                Screen.BIOPORI -> BioporiScreen(token)
                Screen.AKUN -> AccountScreen(token, onLogout)
                Screen.KARTU -> CardScreen(token)
                Screen.HARGA -> PriceScreen(token)
            }
        }
        NavigationBar {
            NavigationBarItem(screen == Screen.DASHBOARD, { screen = Screen.DASHBOARD }, label = { Text("Beranda") }, icon = {})
            NavigationBarItem(screen == Screen.TRANSAKSI, { screen = Screen.TRANSAKSI }, label = { Text("Riwayat") }, icon = {})
            NavigationBarItem(screen == Screen.BIOPORI, { screen = Screen.BIOPORI }, label = { Text("Organik") }, icon = {})
            NavigationBarItem(screen == Screen.AKUN, { screen = Screen.AKUN }, label = { Text("Akun") }, icon = {})
            NavigationBarItem(screen == Screen.KARTU, { screen = Screen.KARTU }, label = { Text("Kartu") }, icon = {})
            NavigationBarItem(screen == Screen.HARGA, { screen = Screen.HARGA }, label = { Text("Harga") }, icon = {})
        }
    }
}

@Composable
private fun DashboardScreen(token: String) {
    var data by remember { mutableStateOf<DashboardResponse?>(null) }
    var error by remember { mutableStateOf<String?>(null) }
    LaunchedEffect(token) { runCatching { ApiClient.api.dashboard("Bearer $token") }.onSuccess { data = it }.onFailure { error = "Dashboard belum dapat dimuat. Periksa koneksi." } }
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(24.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        Text("EcoWin", style = MaterialTheme.typography.headlineMedium)
        Text("Ringkasan pengelolaan sampahmu")
        data?.let {
            ElevatedCard(Modifier.fillMaxWidth()) { Column(Modifier.padding(18.dp)) { Text("Saldo anorganik"); Text("Rp ${it.total_saldo}", style = MaterialTheme.typography.headlineMedium) } }
            Text("Anorganik terkumpul: ${it.total_berat_anorganik} kg")
            Text("Organik dikelola: ${it.total_organik} kg")
            Text("Perkiraan kompos: ${it.estimasi_kompos} kg")
            Text("Laporan Biopori menunggu: ${it.biopori_menunggu}")
        } ?: Text(error ?: "Memuat data...")
    }
}

@Composable
private fun AccountScreen(token: String, onLogout: () -> Unit) {
    var oldPin by remember { mutableStateOf("") }
    var newPin by remember { mutableStateOf("") }
    var message by remember { mutableStateOf<String?>(null) }
    var loading by remember { mutableStateOf(false) }
    var withdrawal by remember { mutableStateOf("") }
    var withdrawals by remember { mutableStateOf<List<Withdrawal>>(emptyList()) }
    val scope = rememberCoroutineScope()
    Column(Modifier.fillMaxSize().padding(24.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        Text("Akun", style = MaterialTheme.typography.headlineMedium)
        var profile by remember { mutableStateOf<ProfileResponse?>(null) }
        LaunchedEffect(token) {
            runCatching { ApiClient.api.profile("Bearer $token") }.onSuccess { profile = it }
            runCatching { ApiClient.api.withdrawals("Bearer $token") }.onSuccess { withdrawals = it }
        }
        profile?.let { Text("${it.nasabah.nama}\n${it.nasabah.username}\n${it.nasabah.no_hp}\nSaldo tersedia: Rp ${it.saldo}") }
        OutlinedTextField(withdrawal, { withdrawal = it.filter(Char::isDigit) }, label = { Text("Jumlah penarikan") }, modifier = Modifier.fillMaxWidth(), singleLine = true)
        Button(enabled = !loading && withdrawal.toIntOrNull()?.let { it > 0 } == true, onClick = {
            scope.launch {
                loading = true
                runCatching { ApiClient.api.requestWithdrawal("Bearer $token", WithdrawalRequest(withdrawal.toInt())) }
                    .onSuccess { message = "Permintaan penarikan dikirim dan menunggu petugas."; withdrawal = ""; withdrawals = ApiClient.api.withdrawals("Bearer $token") }
                    .onFailure { message = "Permintaan gagal. Periksa saldo dan koneksi." }
                loading = false
            }
        }) { Text("Ajukan penarikan") }
        Text("Riwayat penarikan", style = MaterialTheme.typography.titleMedium)
        withdrawals.forEach { Text("Rp ${it.jumlah} • ${it.status} • ${it.created_at.orEmpty()}") }
        OutlinedTextField(oldPin, { oldPin = it }, label = { Text("PIN lama") }, modifier = Modifier.fillMaxWidth(), singleLine = true)
        OutlinedTextField(newPin, { newPin = it }, label = { Text("PIN baru (4–12 karakter)") }, modifier = Modifier.fillMaxWidth(), singleLine = true)
        Button(enabled = !loading && oldPin.isNotBlank() && newPin.length in 4..12, onClick = {
            scope.launch {
                loading = true
                runCatching { ApiClient.api.changePin("Bearer $token", ChangePinRequest(oldPin, newPin)) }
                    .onSuccess { message = "PIN berhasil diganti."; oldPin = ""; newPin = "" }
                    .onFailure { message = "Gagal mengganti PIN. Periksa PIN lama dan koneksi." }
                loading = false
            }
        }) { Text(if (loading) "Memproses..." else "Ganti PIN") }
        message?.let { Text(it) }
        OutlinedButton(onClick = {
            scope.launch { runCatching { ApiClient.api.logout("Bearer $token") }; onLogout() }
        }) { Text("Keluar") }
    }
}

@Composable
private fun CardScreen(token: String) {
    var card by remember { mutableStateOf<CardResponse?>(null) }
    LaunchedEffect(token) { runCatching { ApiClient.api.card("Bearer $token") }.onSuccess { card = it } }
    Column(Modifier.fillMaxSize().padding(24.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        Text("Kartu Nasabah", style = MaterialTheme.typography.headlineMedium)
        card?.let {
            Text(it.nama, style = MaterialTheme.typography.titleLarge)
            Text("Username: ${it.username}")
            androidx.compose.foundation.Image(
                bitmap = remember(it.qr_token) { qrBitmap(it.qr_token) },
                contentDescription = "QR nasabah",
                modifier = Modifier.size(220.dp),
            )
            Text("Tunjukkan kartu ini kepada petugas untuk dipindai.")
        } ?: Text("Memuat kartu...")
    }
}

@Composable
private fun PriceScreen(token: String) {
    var prices by remember { mutableStateOf<List<PriceCategory>?>(null) }
    LaunchedEffect(token) { runCatching { ApiClient.api.prices("Bearer $token") }.onSuccess { prices = it } }
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(24.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        Text("Harga Sampah Anorganik", style = MaterialTheme.typography.headlineMedium)
        prices?.forEach { category ->
            Text(category.nama_kategori, style = MaterialTheme.typography.titleMedium)
            category.jenis_sampah.forEach { type ->
                Text(type.nama_jenis)
                type.harga.forEach { Text("${it.kondisi} • Rp ${it.harga_per_kg}/kg") }
            }
        } ?: Text("Memuat harga...")
    }
}

@Composable
private fun TransactionScreen(token: String) {
    var data by remember { mutableStateOf<TransactionResponse?>(null) }
    var error by remember { mutableStateOf<String?>(null) }
    LaunchedEffect(token) { runCatching { ApiClient.api.transactions("Bearer $token") }.onSuccess { data = it }.onFailure { error = "Riwayat belum dapat dimuat." } }
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(24.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
        Text("Riwayat", style = MaterialTheme.typography.headlineMedium)
        Text("Anorganik", style = MaterialTheme.typography.titleMedium)
        data?.anorganik?.forEach { Text("${it.created_at.orEmpty()} • ${it.berat_kg} kg • Rp ${it.nilai_rupiah}") }
        Text("Organik", style = MaterialTheme.typography.titleMedium)
        data?.organik?.forEach { Text("${it.created_at.orEmpty()} • ${it.jenis_organik} • ${it.berat_kg} kg") }
        if (data == null) Text(error ?: "Memuat riwayat...")
    }
}

@Composable
private fun BioporiScreen(token: String) {
    val context = LocalContext.current
    var activities by remember { mutableStateOf<List<BioporiActivity>>(emptyList()) }
    var description by remember { mutableStateOf("") }
    var date by remember { mutableStateOf(LocalDate.now().toString()) }
    var photoUri by remember { mutableStateOf<Uri?>(null) }
    var message by remember { mutableStateOf<String?>(null) }
    var loading by remember { mutableStateOf(false) }
    val scope = rememberCoroutineScope()
    val picker = rememberLauncherForActivityResult(ActivityResultContracts.GetContent()) { photoUri = it }
    LaunchedEffect(token) { runCatching { ApiClient.api.biopori("Bearer $token") }.onSuccess { activities = it } }
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(24.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
        Text("Jalur Organik", style = MaterialTheme.typography.headlineMedium)
        Text("Catat aktivitas pengelolaan melalui BioporiPrint")
        OutlinedTextField(date, { date = it }, label = { Text("Tanggal (YYYY-MM-DD)") }, modifier = Modifier.fillMaxWidth(), singleLine = true)
        OutlinedTextField(description, { description = it }, label = { Text("Deskripsi aktivitas") }, modifier = Modifier.fillMaxWidth(), minLines = 3)
        Button(onClick = { picker.launch("image/*") }) { Text(if (photoUri == null) "Pilih foto bukti" else "Ganti foto bukti") }
        Button(enabled = !loading && photoUri != null && date.isNotBlank(), onClick = {
            scope.launch {
                loading = true
                val uri = photoUri
                val bytes = uri?.let { context.contentResolver.openInputStream(it)?.use { stream -> stream.readBytes() } }
                if (bytes == null) {
                    message = "Foto tidak dapat dibaca. Silakan pilih ulang."
                } else {
                    val part = MultipartBody.Part.createFormData("foto_bukti", "bukti.jpg", bytes.toRequestBody("image/jpeg".toMediaType()))
                    runCatching { ApiClient.api.submitBiopori("Bearer $token", date.toRequestBody("text/plain".toMediaType()), description.toRequestBody("text/plain".toMediaType()), part) }
                        .onSuccess { activities = ApiClient.api.biopori("Bearer $token"); message = "Laporan terkirim dan menunggu verifikasi."; description = ""; photoUri = null }
                        .onFailure { message = "Laporan gagal dikirim. Periksa tanggal dan koneksi." }
                }
                loading = false
            }
        }) { Text(if (loading) "Mengirim..." else "Kirim laporan") }
        message?.let { Text(it) }
        HorizontalDivider()
        Text("Riwayat aktivitas", style = MaterialTheme.typography.titleMedium)
        activities.forEach { Text("${it.tanggal_pemasukan} • ${it.status} • ${it.deskripsi.orEmpty()}") }
    }
}
