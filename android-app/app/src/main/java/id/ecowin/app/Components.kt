package id.ecowin.app

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

/** Hasil pemuatan data dari API beserta fungsi muat ulang. */
class Loadable<T>(val data: T?, val error: String?, val loading: Boolean, val reload: () -> Unit)

@Composable
fun <T> rememberLoadable(client: ApiClient, vararg keys: Any?, block: suspend () -> T): Loadable<T> {
    var version by remember { mutableIntStateOf(0) }
    var data by remember { mutableStateOf<T?>(null) }
    var error by remember { mutableStateOf<String?>(null) }
    var loading by remember { mutableStateOf(true) }

    LaunchedEffect(version, *keys) {
        loading = true
        error = null
        try {
            data = block()
        } catch (e: Exception) {
            error = client.errorMessage(e)
        }
        loading = false
    }

    return Loadable(data, error, loading) { version++ }
}

@Composable
fun <T> LoadableContent(state: Loadable<T>, content: @Composable (T) -> Unit) {
    val data = state.data
    when {
        data != null -> content(data)
        state.loading -> Box(Modifier.fillMaxWidth().padding(32.dp), contentAlignment = Alignment.Center) { CircularProgressIndicator() }
        else -> ErrorBox(state.error ?: "Gagal memuat data.", state.reload)
    }
}

@Composable
fun ErrorBox(message: String, onRetry: () -> Unit) {
    EcoCard {
        Text(message, color = Red)
        Spacer(Modifier.padding(4.dp))
        OutlinedButton(onClick = onRetry) { Text("Coba lagi") }
    }
}

@Composable
fun EcoCard(modifier: Modifier = Modifier, accent: Color? = null, content: @Composable () -> Unit) {
    Column(
        modifier
            .fillMaxWidth()
            .background(Color.White, RoundedCornerShape(16.dp))
            .border(1.dp, Slate200, RoundedCornerShape(16.dp))
            .padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(6.dp),
    ) {
        if (accent != null) {
            Box(Modifier.width(32.dp).padding(bottom = 2.dp).background(accent, RoundedCornerShape(2.dp)).padding(vertical = 1.5.dp))
        }
        content()
    }
}

@Composable
fun SectionTitle(text: String) {
    Text(text, fontWeight = FontWeight.Bold, fontSize = 16.sp, color = Slate900, modifier = Modifier.padding(top = 8.dp))
}

@Composable
fun LabelValue(label: String, value: String) {
    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
        Text(label, color = Slate500, fontSize = 14.sp)
        Text(value, color = Slate900, fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
    }
}

/** Badge status selalu memakai teks, tidak hanya warna. */
@Composable
fun StatusBadge(status: String?) {
    val (bg, fg) = when (status) {
        "approved", "completed", "selesai", "sudah_panen" -> EmeraldSoft to Color(0xFF065F46)
        "rejected" -> RedSoft to Red
        "siap_panen" -> IndigoSoft to Indigo
        else -> AmberSoft to Amber
    }
    Text(
        statusLabel(status),
        color = fg,
        fontSize = 12.sp,
        fontWeight = FontWeight.SemiBold,
        modifier = Modifier.background(bg, RoundedCornerShape(999.dp)).padding(horizontal = 10.dp, vertical = 3.dp),
    )
}

@Composable
fun StatTile(label: String, value: String, modifier: Modifier = Modifier) {
    Column(
        modifier
            .background(Color.White, RoundedCornerShape(14.dp))
            .border(1.dp, Slate200, RoundedCornerShape(14.dp))
            .padding(12.dp),
    ) {
        Text(label, color = Slate500, fontSize = 12.sp)
        Text(value, color = Slate900, fontWeight = FontWeight.Bold, fontSize = 16.sp)
    }
}

@Composable
fun EmptyText(text: String) {
    Text(text, color = Slate500, fontSize = 14.sp, modifier = Modifier.padding(vertical = 8.dp))
}

@Composable
fun PrimaryButton(text: String, enabled: Boolean = true, loading: Boolean = false, onClick: () -> Unit) {
    Button(onClick = onClick, enabled = enabled && !loading, modifier = Modifier.fillMaxWidth(), shape = RoundedCornerShape(12.dp)) {
        if (loading) CircularProgressIndicator(Modifier.padding(end = 8.dp).size(18.dp), color = MaterialTheme.colorScheme.onPrimary, strokeWidth = 2.dp)
        Text(text)
    }
}
