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
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.ui.layout.layout
import androidx.compose.ui.draw.shadow
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.ui.draw.clip
import androidx.compose.foundation.layout.offset
import androidx.compose.ui.graphics.Brush
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

    Column(Modifier.fillMaxSize().background(Canvas).navigationBarsPadding().verticalScroll(rememberScrollState())) {
        // Kepala hijau EcoWin: merek dan pesan, dengan lingkaran dekoratif
        Box(
            Modifier.fillMaxWidth()
                .clip(RoundedCornerShape(bottomStart = 36.dp, bottomEnd = 36.dp))
                .background(Brush.linearGradient(listOf(Color(0xFF065F46), Emerald, Color(0xFF10B981)))),
        ) {
            Box(Modifier.matchParentSize()) {
                Box(Modifier.align(Alignment.TopEnd).offset(x = 70.dp, y = (-80).dp).size(260.dp).background(Color.White.copy(alpha = 0.10f), CircleShape))
                Box(Modifier.align(Alignment.BottomStart).offset(x = (-70).dp, y = 70.dp).size(200.dp).background(Color.White.copy(alpha = 0.07f), CircleShape))
            }
            Column(
                Modifier.fillMaxWidth().statusBarsPadding().padding(start = 24.dp, end = 24.dp, top = 20.dp, bottom = 84.dp),
                verticalArrangement = Arrangement.spacedBy(6.dp),
            ) {
                Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    Image(
                        painterResource(R.drawable.ecowin_emblem), contentDescription = null,
                        modifier = Modifier.size(44.dp).background(Color.White, RoundedCornerShape(14.dp)).padding(7.dp),
                    )
                    Row {
                        Text("Eco", fontSize = 24.sp, fontWeight = FontWeight.ExtraBold, color = Color.White)
                        Text("Win", fontSize = 24.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFFA7F3D0))
                    }
                }
                Text("Kelola Sampah.", fontSize = 30.sp, lineHeight = 34.sp, fontWeight = FontWeight.ExtraBold, color = Color.White, modifier = Modifier.padding(top = 16.dp))
                Text("Jaga Masa Depan.", fontSize = 30.sp, lineHeight = 34.sp, fontWeight = FontWeight.ExtraBold, color = Color(0xFFA7F3D0))
                Text("Bank sampah digital untuk lingkungan yang lebih baik.", color = Color(0xFFECFDF5).copy(alpha = 0.88f), fontSize = 15.sp, modifier = Modifier.padding(top = 4.dp, end = 40.dp))
            }
        }

        // Kartu masuk: menimpa tepi bawah kepala hijau
        Column(
            Modifier.fillMaxWidth().padding(horizontal = 16.dp).overlapAtas(56.dp)
                .shadow(14.dp, RoundedCornerShape(28.dp), ambientColor = Color(0x33065F46), spotColor = Color(0x59065F46))
                .background(Color.White, RoundedCornerShape(28.dp))
                .border(1.dp, Slate200, RoundedCornerShape(28.dp))
                .padding(22.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            Text(
                "MASUK KE ECOWIN", color = EmeraldDeep, fontSize = 11.sp, fontWeight = FontWeight.Bold, letterSpacing = 1.sp,
                modifier = Modifier.background(EmeraldSoft, RoundedCornerShape(999.dp)).padding(horizontal = 12.dp, vertical = 5.dp),
            )
            Text("Selamat datang", fontSize = 26.sp, fontWeight = FontWeight.ExtraBold, color = Slate900, modifier = Modifier.padding(top = 2.dp))
            Text("Masuk dengan akun Google Anda untuk melihat saldo, setoran, dan aktivitas organik.", color = Slate500, fontSize = 15.sp)

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
                    .background(if (loading) EmeraldSoft else Color.White, RoundedCornerShape(50))
                    .border(1.5.dp, if (loading) Color(0xFFA7F3D0) else Color(0xFFCBD5E1), RoundedCornerShape(50))
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

            Box(Modifier.fillMaxWidth().padding(top = 8.dp).height(1.dp).background(Slate200))
            Column(Modifier.fillMaxWidth().padding(top = 4.dp), horizontalAlignment = Alignment.CenterHorizontally, verticalArrangement = Arrangement.spacedBy(10.dp)) {
                Text("Belum punya akun?", color = Slate500, fontSize = 14.sp)
                Box(
                    Modifier.fillMaxWidth().heightIn(min = 52.dp).background(EmeraldSoft, RoundedCornerShape(50))
                        .border(1.dp, Color(0xFFA7F3D0), RoundedCornerShape(50))
                        .clickable(role = Role.Button) { registering = true },
                    contentAlignment = Alignment.Center,
                ) { Text("Daftar sebagai nasabah", color = EmeraldDeep, fontWeight = FontWeight.Bold, fontSize = 15.sp) }
            }
        }

        Text(
            "© ${java.time.Year.now().value} EcoWin · Bank Sampah Digital Berkelanjutan", color = Color(0xFF94A3B8), fontSize = 12.sp,
            textAlign = TextAlign.Center, modifier = Modifier.fillMaxWidth().padding(top = 8.dp, bottom = 24.dp),
        )
    }
}

/** Menggeser elemen ke atas sebesar [jarak] dan ikut mengurangi tinggi tata letaknya, sehingga menimpa elemen di atasnya tanpa celah. */
private fun Modifier.overlapAtas(jarak: androidx.compose.ui.unit.Dp): Modifier = this.layout { ukur, batas ->
    val p = ukur.measure(batas)
    val j = jarak.roundToPx()
    layout(p.width, (p.height - j).coerceAtLeast(0)) { p.place(0, -j) }
}
