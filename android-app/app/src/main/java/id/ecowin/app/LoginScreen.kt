package id.ecowin.app

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
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.statusBarsPadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.launch

/**
 * Login Nasabah (Android native). Hanya Google: ID token dikirim ke server yang memverifikasi email, audience,
 * dan menentukan role. Tidak ada password, PIN, atau pilihan role. Identitas visual sama dengan halaman login Web.
 */
@Composable
fun LoginScreen(
    initialMessage: String?,
    onGoogleToken: suspend (String) -> Unit,
    googleAuth: GoogleAuth,
    client: ApiClient,
    onRegistered: (LoginResponse) -> Unit,
) {
    var registering by remember { mutableStateOf(false) }
    if (registering) {
        RegisterScreen(googleAuth, client, onRegistered = onRegistered, onBack = { registering = false })
        return
    }
    var message by remember { mutableStateOf(initialMessage) }
    var loading by remember { mutableStateOf(false) }
    val scope = rememberCoroutineScope()

    Column(
        Modifier.fillMaxSize().background(EmeraldSoft).statusBarsPadding().navigationBarsPadding().verticalScroll(rememberScrollState()),
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        // Identitas + ilustrasi
        Column(Modifier.fillMaxWidth().padding(start = 24.dp, end = 24.dp, top = 28.dp), verticalArrangement = Arrangement.spacedBy(12.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                Image(painterResource(R.drawable.ecowin_emblem), contentDescription = null, modifier = Modifier.size(42.dp).background(Color.Black, RoundedCornerShape(14.dp)).padding(6.dp))
                Row {
                    Text("Eco", fontSize = 24.sp, fontWeight = FontWeight.ExtraBold, color = Slate900)
                    Text("Win", fontSize = 24.sp, fontWeight = FontWeight.ExtraBold, color = Emerald)
                }
            }
            Text("Kelola Sampah.", fontSize = 30.sp, lineHeight = 33.sp, fontWeight = FontWeight.ExtraBold, color = Slate900)
            Text("Jaga Masa Depan.", fontSize = 30.sp, lineHeight = 33.sp, fontWeight = FontWeight.ExtraBold, color = Emerald)
            Text("Bank sampah digital untuk lingkungan yang lebih baik.", color = Color(0xFF475569), fontSize = 15.sp)
        }

        Image(
            painterResource(R.drawable.ill_ecowin),
            contentDescription = null,
            contentScale = ContentScale.Fit,
            modifier = Modifier.fillMaxWidth(0.72f).heightIn(max = 220.dp).padding(vertical = 12.dp),
        )

        // Panel autentikasi
        Column(
            Modifier.fillMaxWidth().padding(horizontal = 16.dp).padding(bottom = 24.dp)
                .background(Color.White, RoundedCornerShape(24.dp))
                .border(1.dp, Slate200, RoundedCornerShape(24.dp))
                .padding(22.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            Text(
                "MASUK KE ECOWIN", color = Color(0xFF047857), fontSize = 11.sp, fontWeight = FontWeight.Bold, letterSpacing = 1.sp,
                modifier = Modifier.background(EmeraldSoft, RoundedCornerShape(999.dp)).border(1.dp, Color(0xFFA7F3D0), RoundedCornerShape(999.dp)).padding(horizontal = 12.dp, vertical = 5.dp),
            )
            Text("Selamat datang", fontSize = 26.sp, fontWeight = FontWeight.ExtraBold, color = Slate900)
            Text("Masuk dengan akun Google Anda untuk melihat saldo, setoran, dan aktivitas organik.", color = Slate500, fontSize = 14.sp)

            message?.let {
                Text(
                    it, color = Red, fontSize = 13.sp,
                    modifier = Modifier.fillMaxWidth().background(RedSoft, RoundedCornerShape(14.dp)).padding(12.dp)
                        .semantics { contentDescription = "Kesalahan: $it" },
                )
            }

            // Tombol Google: target sentuh 56dp, logo "G" resmi, status memuat.
            val deskripsi = if (loading) "Mengalihkan ke Google" else "Lanjutkan dengan Google"
            Row(
                Modifier.fillMaxWidth().padding(top = 8.dp).heightIn(min = 56.dp)
                    .background(Color.White, RoundedCornerShape(16.dp))
                    .border(1.dp, Color(0xFFCBD5E1), RoundedCornerShape(16.dp))
                    .clickable(enabled = !loading, role = Role.Button, onClickLabel = deskripsi) {
                        loading = true
                        message = null
                        scope.launch {
                            when (val result = googleAuth.signIn()) {
                                is GoogleAuth.Result.Success -> try {
                                    onGoogleToken(result.idToken)
                                } catch (e: Exception) {
                                    message = if (e is IllegalStateException) e.message else client.errorMessage(e)
                                }
                                is GoogleAuth.Result.Failure -> message = result.message
                                GoogleAuth.Result.Cancelled -> message =
                                    "Pemilihan akun Google ditutup. Jika Anda tidak membatalkannya, Google belum mengenali aplikasi ini: " +
                                        "buat OAuth client tipe Android (package id.ecowin.app + SHA-1) di Google Cloud dan daftarkan email Anda sebagai Test user."
                            }
                            loading = false
                        }
                    }
                    .padding(horizontal = 16.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.Center,
            ) {
                if (loading) CircularProgressIndicator(Modifier.size(22.dp), strokeWidth = 2.dp, color = Emerald)
                else Image(painterResource(R.drawable.ic_google), contentDescription = null, modifier = Modifier.size(22.dp))
                Text(deskripsi + if (loading) "…" else "", modifier = Modifier.padding(start = 12.dp), fontWeight = FontWeight.SemiBold, color = Slate900, fontSize = 16.sp)
            }

            Row(Modifier.padding(top = 4.dp), horizontalArrangement = Arrangement.spacedBy(8.dp), verticalAlignment = Alignment.Top) {
                Icon(Lucide.ShieldCheck, contentDescription = null, tint = Emerald, modifier = Modifier.size(16.dp).padding(top = 2.dp))
                Text("Akun Google harus memiliki email terverifikasi dan terdaftar di EcoWin.", color = Slate500, fontSize = 13.sp)
            }

            Row(Modifier.fillMaxWidth().padding(top = 8.dp), horizontalArrangement = Arrangement.Center) {
                Text("Belum punya akun? ", color = Slate500, fontSize = 14.sp)
                Text("Daftar sebagai nasabah", color = Color(0xFF047857), fontWeight = FontWeight.Bold, fontSize = 14.sp, modifier = Modifier.clickable { registering = true })
            }
            Text("Langkah kecil, dampak besar.", color = Color(0xFF047857), fontSize = 12.sp, fontWeight = FontWeight.SemiBold, textAlign = TextAlign.Center, modifier = Modifier.fillMaxWidth().padding(top = 6.dp))
        }
    }
}
