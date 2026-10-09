package id.ecowin.app

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import kotlinx.coroutines.launch
import retrofit2.HttpException

/**
 * Alur sesi:
 *  - Buka aplikasi -> cek token tersimpan -> masih valid? langsung ke Beranda (tanpa login Google lagi).
 *  - Access token kedaluwarsa -> ApiClient refresh otomatis memakai refresh token.
 *  - Refresh token dicabut/kedaluwarsa/akun dinonaktifkan -> kembali ke layar login.
 */
class MainActivity : ComponentActivity() {

    private lateinit var session: SessionStore
    private lateinit var client: ApiClient
    private lateinit var googleAuth: GoogleAuth

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        session = SessionStore(this)
        client = ApiClient(session)
        googleAuth = GoogleAuth(this)

        setContent {
            EcoWinTheme {
                Surface(Modifier.fillMaxSize(), color = MaterialTheme.colorScheme.background) {
                    EcoWinRoot(session, client, googleAuth)
                }
            }
        }
    }
}

private sealed interface AppState {
    data object Checking : AppState
    data class Offline(val message: String) : AppState
    data class LoggedOut(val message: String? = null) : AppState
    data class LoggedIn(val user: UserDto) : AppState
}

@Composable
private fun EcoWinRoot(session: SessionStore, client: ApiClient, googleAuth: GoogleAuth) {
    var state by remember { mutableStateOf<AppState>(if (session.hasSession()) AppState.Checking else AppState.LoggedOut()) }
    var retry by remember { mutableStateOf(0) }
    val scope = rememberCoroutineScope()

    LaunchedEffect(Unit) {
        client.sessionExpired.collect {
            state = AppState.LoggedOut("Sesi Anda berakhir. Silakan masuk kembali dengan Google.")
        }
    }

    LaunchedEffect(retry) {
        if (state is AppState.Checking) {
            state = try {
                val user = client.api.me().data
                if (user.role == "nasabah") AppState.LoggedIn(user) else {
                    session.clear()
                    AppState.LoggedOut("Aplikasi ini khusus nasabah. Admin/Petugas gunakan dashboard web.")
                }
            } catch (e: HttpException) {
                if (e.code() == 401 || e.code() == 403) {
                    session.clear()
                    AppState.LoggedOut("Sesi Anda berakhir. Silakan masuk kembali.")
                } else AppState.Offline(client.errorMessage(e))
            } catch (e: Exception) {
                AppState.Offline(client.errorMessage(e))
            }
        }
    }

    when (val s = state) {
        AppState.Checking -> CenteredProgress()
        is AppState.Offline -> Column(
            Modifier.fillMaxSize().padding(24.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp, Alignment.CenterVertically),
            horizontalAlignment = Alignment.CenterHorizontally,
        ) {
            Text(s.message, textAlign = TextAlign.Center)
            Button(onClick = { state = AppState.Checking; retry++ }) { Text("Coba lagi") }
        }
        is AppState.LoggedOut -> LoginScreen(
            initialMessage = s.message,
            onGoogleToken = { idToken ->
                val response = client.api.loginGoogle(GoogleLoginRequest(idToken, android.os.Build.MODEL ?: "android"))
                if (response.user.role != "nasabah") {
                    // Simpan sementara agar bisa dicabut di server, lalu langsung logout.
                    session.save(response.tokens())
                    runCatching { client.api.logout() }
                    session.clear()
                    throw IllegalStateException("Aplikasi ini khusus nasabah. Admin/Petugas gunakan dashboard web.")
                }
                session.save(response.tokens())
                state = AppState.LoggedIn(response.user)
            },
            googleAuth = googleAuth,
            client = client,
        )
        is AppState.LoggedIn -> MainScreen(
            user = s.user,
            client = client,
            onUserChanged = { state = AppState.LoggedIn(it) },
            onLogout = {
                scope.launch {
                    runCatching { client.api.logout() }
                    session.clear()
                    googleAuth.signOut()
                    state = AppState.LoggedOut()
                }
            },
        )
    }
}

@Composable
fun CenteredProgress() {
    Column(Modifier.fillMaxSize(), verticalArrangement = Arrangement.Center, horizontalAlignment = Alignment.CenterHorizontally) {
        CircularProgressIndicator()
    }
}
