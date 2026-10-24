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
import androidx.compose.ui.unit.em
import androidx.compose.ui.unit.sp

val Emerald = Color(0xFF059669)
val EmeraldSoft = Color(0xFFECFDF5)
val EmeraldDeep = Color(0xFF047857)
val Slate900 = Color(0xFF0F172A)
val Slate500 = Color(0xFF64748B)
val Slate200 = Color(0xFFE2E8F0)
val Canvas = Color(0xFFF8FAFC)
/** Garis tepi kolom isian: kontras >= 3:1 terhadap putih (WCAG 1.4.11). */
val FieldBorder = Color(0xFF8392A6)
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
    fun androidx.compose.ui.text.TextStyle.eco() = copy(fontFamily = Inter, letterSpacing = 0.sp, lineHeight = 1.4.em)
    return d.copy(
        displayLarge = d.displayLarge.eco(),
        displayMedium = d.displayMedium.eco(),
        displaySmall = d.displaySmall.eco(),
        headlineLarge = d.headlineLarge.eco(),
        headlineMedium = d.headlineMedium.eco(),
        headlineSmall = d.headlineSmall.eco(),
        titleLarge = d.titleLarge.eco(),
        titleMedium = d.titleMedium.eco(),
        titleSmall = d.titleSmall.eco(),
        bodyLarge = d.bodyLarge.eco(),
        bodyMedium = d.bodyMedium.eco(),
        bodySmall = d.bodySmall.eco(),
        labelLarge = d.labelLarge.eco(),
        labelMedium = d.labelMedium.eco(),
        labelSmall = d.labelSmall.eco(),
    )
}

@Composable
fun EcoWinTheme(content: @Composable () -> Unit) {
    // Ukuran huruf mengikuti Web (16px = 1x): skala huruf sistem di bawah 1x tidak dipakai agar tampilan sama dengan Web; skala besar (aksesibilitas) tetap dihormati sampai 1,3x.
    val kepadatan = androidx.compose.ui.platform.LocalDensity.current
    androidx.compose.runtime.CompositionLocalProvider(
        androidx.compose.ui.platform.LocalDensity provides androidx.compose.ui.unit.Density(kepadatan.density, kepadatan.fontScale.coerceIn(1f, 1.3f)),
    ) {
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
            outline = FieldBorder,
            error = Red,
        ),
        content = content,
    )
    }
}
