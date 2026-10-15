package id.ecowin.app

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Typography
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.ExperimentalTextApi
import androidx.compose.ui.text.font.Font
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontVariation
import androidx.compose.ui.text.font.FontWeight

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

/** Inter (font variabel yang sama dengan Web) agar huruf di Android dan Web identik. */
@OptIn(ExperimentalTextApi::class)
val Inter = FontFamily(
    listOf(400, 500, 600, 700, 800).map { w ->
        Font(R.font.inter_variable, FontWeight(w), variationSettings = FontVariation.Settings(FontVariation.weight(w)))
    },
)

private fun interTypography(): Typography {
    val d = Typography()
    return d.copy(
        displayLarge = d.displayLarge.copy(fontFamily = Inter),
        displayMedium = d.displayMedium.copy(fontFamily = Inter),
        displaySmall = d.displaySmall.copy(fontFamily = Inter),
        headlineLarge = d.headlineLarge.copy(fontFamily = Inter),
        headlineMedium = d.headlineMedium.copy(fontFamily = Inter),
        headlineSmall = d.headlineSmall.copy(fontFamily = Inter),
        titleLarge = d.titleLarge.copy(fontFamily = Inter),
        titleMedium = d.titleMedium.copy(fontFamily = Inter),
        titleSmall = d.titleSmall.copy(fontFamily = Inter),
        bodyLarge = d.bodyLarge.copy(fontFamily = Inter),
        bodyMedium = d.bodyMedium.copy(fontFamily = Inter),
        bodySmall = d.bodySmall.copy(fontFamily = Inter),
        labelLarge = d.labelLarge.copy(fontFamily = Inter),
        labelMedium = d.labelMedium.copy(fontFamily = Inter),
        labelSmall = d.labelSmall.copy(fontFamily = Inter),
    )
}

@Composable
fun EcoWinTheme(content: @Composable () -> Unit) {
    MaterialTheme(
        typography = interTypography(),
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
