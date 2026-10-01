package id.ecowin.app

import android.net.Uri
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.compose.setContent
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import kotlinx.coroutines.launch
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.toRequestBody
import java.time.OffsetDateTime

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent { EcoWinApp() }
    }
}

private enum class Screen { DASHBOARD, TRANSAKSI, BIOPORI }

@Composable
private fun EcoWinApp() {
    var token by remember { mutableStateOf<String?>(null) }
    Surface(Modifier.fillMaxSize()) {
        if (token == null) LoginScreen { token = it } else MainScreen(token!!)
    }
}

@Composable
private fun LoginScreen(onLoggedIn: (String) -> Unit) {
    var phone by remember { mutableStateOf("") }
    var pin by remember { mutableStateOf("") }
    var message by remember { mutableStateOf<String?>(null) }
    var loading by remember { mutableStateOf(false) }
    val scope = rememberCoroutineScope()
    Column(Modifier.fillMaxSize().padding(24.dp), verticalArrangement = Arrangement.Center) {
        Text("EcoWin", style = MaterialTheme.typography.headlineLarge)
        Text("Login Nasabah")
        Spacer(Modifier.height(20.dp))
        OutlinedTextField(phone, { phone = it }, label = { Text("Nomor HP") }, modifier = Modifier.fillMaxWidth())
        OutlinedTextField(pin, { pin = it }, label = { Text("PIN") }, modifier = Modifier.fillMaxWidth())
        Spacer(Modifier.height(12.dp))
        Button(enabled = !loading, modifier = Modifier.fillMaxWidth(), onClick = {
            scope.launch {
                loading = true
                runCatching { ApiClient.api.login(LoginRequest(phone, pin)).token }
                    .onSuccess(onLoggedIn).onFailure { message = "Login gagal. Periksa nomor HP dan PIN." }
                loading = false
            }
        }) { Text(if (loading) "Memuat..." else "Masuk") }
        message?.let { Text(it, color = MaterialTheme.colorScheme.error) }
    }
}

@Composable
private fun MainScreen(token: String) {
    var screen by remember { mutableStateOf(Screen.DASHBOARD) }
    Column(Modifier.fillMaxSize()) {
        Box(Modifier.weight(1f)) {
            when (screen) {
                Screen.DASHBOARD -> DashboardScreen(token)
                Screen.TRANSAKSI -> TransactionScreen(token)
                Screen.BIOPORI -> BioporiScreen(token)
            }
        }
        NavigationBar {
            NavigationBarItem(screen == Screen.DASHBOARD, { screen = Screen.DASHBOARD }, label = { Text("Beranda") }, icon = {})
            NavigationBarItem(screen == Screen.TRANSAKSI, { screen = Screen.TRANSAKSI }, label = { Text("Transaksi") }, icon = {})
            NavigationBarItem(screen == Screen.BIOPORI, { screen = Screen.BIOPORI }, label = { Text("Biopori") }, icon = {})
        }
    }
}

@Composable
private fun DashboardScreen(token: String) {
    var data by remember { mutableStateOf<DashboardResponse?>(null) }
    var error by remember { mutableStateOf<String?>(null) }
    LaunchedEffect(token) { runCatching { ApiClient.api.dashboard("Bearer $token") }.onSuccess { data = it }.onFailure { error = "Dashboard belum dapat dimuat." } }
    Column(Modifier.fillMaxSize().padding(24.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
        Text("Dashboard EcoWin", style = MaterialTheme.typography.headlineMedium)
        data?.let { Text("Saldo: Rp ${it.total_saldo}\nAnorganik: ${it.total_berat_anorganik} kg\nOrganik: ${it.total_organik} kg\nEstimasi kompos: ${it.estimasi_kompos} kg\nBiopori menunggu: ${it.biopori_menunggu}") } ?: Text(error ?: "Memuat data...")
    }
}

@Composable
private fun TransactionScreen(token: String) {
    var data by remember { mutableStateOf<TransactionResponse?>(null) }
    LaunchedEffect(token) { runCatching { ApiClient.api.transactions("Bearer $token") }.onSuccess { data = it } }
    Column(Modifier.fillMaxSize().padding(24.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
        Text("Riwayat Transaksi", style = MaterialTheme.typography.headlineMedium)
        data?.let { result ->
            result.anorganik.forEach { Text("Anorganik • ${it.berat_kg} kg • Rp ${it.nilai_rupiah}") }
            result.organik.forEach { Text("Organik • ${it.jenis_organik} • ${it.berat_kg} kg") }
        } ?: Text("Memuat riwayat...")
    }
}

@Composable
private fun BioporiScreen(token: String) {
    val context = LocalContext.current
    var activities by remember { mutableStateOf<List<BioporiActivity>>(emptyList()) }
    var description by remember { mutableStateOf("") }
    var photoUri by remember { mutableStateOf<Uri?>(null) }
    var message by remember { mutableStateOf<String?>(null) }
    val scope = rememberCoroutineScope()
    val picker = rememberLauncherForActivityResult(ActivityResultContracts.GetContent()) { photoUri = it }
    LaunchedEffect(token) { runCatching { ApiClient.api.biopori("Bearer $token") }.onSuccess { activities = it } }
    Column(Modifier.fillMaxSize().padding(24.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
        Text("BioporiPrint", style = MaterialTheme.typography.headlineMedium)
        OutlinedTextField(description, { description = it }, label = { Text("Deskripsi aktivitas") }, modifier = Modifier.fillMaxWidth())
        Button(onClick = { picker.launch("image/*") }) { Text(if (photoUri == null) "Pilih foto bukti" else "Foto dipilih") }
        Button(enabled = photoUri != null, onClick = {
            scope.launch {
                val uri = photoUri ?: return@launch
                val bytes = context.contentResolver.openInputStream(uri)?.readBytes() ?: return@launch
                val body = bytes.toRequestBody("image/*".toMediaType())
                runCatching { ApiClient.api.submitBiopori("Bearer $token", OffsetDateTime.now().toString().toRequestBody(), description.toRequestBody(), MultipartBody.Part.createFormData("foto_bukti", "bukti.jpg", body)) }
                    .onSuccess { message = "Laporan terkirim dan menunggu verifikasi." }.onFailure { message = "Laporan gagal dikirim." }
            }
        }) { Text("Kirim laporan") }
        message?.let { Text(it) }
        activities.forEach { Text("${it.tanggal_pemasukan} • ${it.status}") }
    }
}
