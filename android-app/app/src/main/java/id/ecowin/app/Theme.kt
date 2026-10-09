package id.ecowin.app

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color

val Emerald = Color(0xFF059669)
val EmeraldSoft = Color(0xFFECFDF5)
val EmeraldDeep = Color(0xFF047857)
val Slate900 = Color(0xFF0F172A)
val Slate500 = Color(0xFF64748B)
val Slate200 = Color(0xFFE2E8F0)
val Canvas = Color(0xFFF8FAFC)
val Amber = Color(0xFFB45309)
val AmberSoft = Color(0xFFFEF3C7)
val Red = Color(0xFFB91C1C)
val RedSoft = Color(0xFFFEE2E2)
val Indigo = Color(0xFF4F46E5)
val IndigoSoft = Color(0xFFEEF2FF)

@Composable
fun EcoWinTheme(content: @Composable () -> Unit) {
    MaterialTheme(
        colorScheme = lightColorScheme(
            primary = Emerald,
            onPrimary = Color.White,
            secondary = Color(0xFF10B981),
            primaryContainer = EmeraldSoft,
            onPrimaryContainer = Color(0xFF065F46),
            background = Canvas,
            surface = Color.White,
            onSurface = Slate900,
            onSurfaceVariant = Slate500,
            outline = Slate200,
            error = Red,
        ),
        content = content,
    )
}
