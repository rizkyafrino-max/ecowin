package id.ecowin.app

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.launch

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
        Modifier.fillMaxSize().padding(28.dp),
        verticalArrangement = Arrangement.spacedBy(14.dp, Alignment.CenterVertically),
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        Text("EcoWin", fontSize = 34.sp, fontWeight = FontWeight.ExtraBold, color = Emerald)
        Text("Bank Sampah Digital Berkelanjutan", color = Slate500, textAlign = TextAlign.Center)

        OutlinedButton(
            onClick = {
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
                        GoogleAuth.Result.Cancelled -> message = "Pemilihan akun Google ditutup. Jika Anda tidak membatalkannya, Google belum mengenali aplikasi ini: buat OAuth client tipe Android (package id.ecowin.app + SHA-1 kunci debug) di Google Cloud, dan pastikan email Anda terdaftar sebagai Test user."
                    }
                    loading = false
                }
            },
            enabled = !loading,
            modifier = Modifier.fillMaxWidth().height(52.dp).padding(top = 12.dp),
            shape = RoundedCornerShape(12.dp),
            border = BorderStroke(1.dp, Slate200),
        ) {
            if (loading) {
                CircularProgressIndicator(Modifier.size(20.dp), strokeWidth = 2.dp)
            } else {
                Text("G  ", color = Indigo, fontWeight = FontWeight.Bold)
                Text("Continue with Google", color = Slate900, fontWeight = FontWeight.SemiBold)
            }
        }

        Text(
            "Gunakan akun Google yang telah terverifikasi untuk mengakses EcoWin.",
            color = Slate500,
            fontSize = 13.sp,
            textAlign = TextAlign.Center,
        )

        TextButton(onClick = { registering = true }) {
            Text("Belum punya akun? ", color = Slate500)
            Text("Daftar akun baru", color = Emerald, fontWeight = FontWeight.Bold)
        }

        message?.let { Text(it, color = Red, fontSize = 14.sp, textAlign = TextAlign.Center) }
    }
}
