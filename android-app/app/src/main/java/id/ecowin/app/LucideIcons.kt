package id.ecowin.app

import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.SolidColor
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.StrokeJoin
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.graphics.vector.PathParser
import androidx.compose.ui.unit.dp

/**
 * Ikon Lucide (24x24, garis 2, ujung bulat): data jalur disalin dari paket lucide-react yang dipakai Web,
 * sehingga ikon Android identik dengan Web. File ini dihasilkan otomatis; jangan diedit manual.
 */
object Lucide {
    private fun icon(name: String, vararg paths: String): ImageVector =
        ImageVector.Builder(name, 24.dp, 24.dp, 24f, 24f).apply {
            paths.forEach { d ->
                addPath(
                    pathData = PathParser().parsePathString(d).toNodes(),
                    fill = null,
                    stroke = SolidColor(Color.Black),
                    strokeLineWidth = 2f,
                    strokeLineCap = StrokeCap.Round,
                    strokeLineJoin = StrokeJoin.Round,
                )
            }
        }.build()

    val Home: ImageVector by lazy { icon("Home",
        "M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8",
        "M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"
    ) }

    val ArrowLeftRight: ImageVector by lazy { icon("ArrowLeftRight",
        "M8 3 4 7l4 4",
        "M4 7h16",
        "m16 21 4-4-4-4",
        "M20 17H4"
    ) }

    val Recycle: ImageVector by lazy { icon("Recycle",
        "M7 19H4.815a1.83 1.83 0 0 1-1.57-.881 1.785 1.785 0 0 1-.004-1.784L7.196 9.5",
        "M11 19h8.203a1.83 1.83 0 0 0 1.556-.89 1.784 1.784 0 0 0 0-1.775l-1.226-2.12",
        "m14 16-3 3 3 3",
        "M8.293 13.596 7.196 9.5 3.1 10.598",
        "m9.344 5.811 1.093-1.892A1.83 1.83 0 0 1 11.985 3a1.784 1.784 0 0 1 1.546.888l3.943 6.843",
        "m13.378 9.633 4.096 1.098 1.097-4.096"
    ) }

    val Wallet: ImageVector by lazy { icon("Wallet",
        "M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1",
        "M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"
    ) }

    val LayoutGrid: ImageVector by lazy { icon("LayoutGrid",
        "M4 3h5a1 1 0 0 1 1 1v5a1 1 0 0 1 -1 1h-5a1 1 0 0 1 -1 -1v-5a1 1 0 0 1 1 -1Z",
        "M15 3h5a1 1 0 0 1 1 1v5a1 1 0 0 1 -1 1h-5a1 1 0 0 1 -1 -1v-5a1 1 0 0 1 1 -1Z",
        "M15 14h5a1 1 0 0 1 1 1v5a1 1 0 0 1 -1 1h-5a1 1 0 0 1 -1 -1v-5a1 1 0 0 1 1 -1Z",
        "M4 14h5a1 1 0 0 1 1 1v5a1 1 0 0 1 -1 1h-5a1 1 0 0 1 -1 -1v-5a1 1 0 0 1 1 -1Z"
    ) }

    val UserRound: ImageVector by lazy { icon("UserRound",
        "M7 8a5 5 0 1 0 10 0a5 5 0 1 0 -10 0",
        "M20 21a8 8 0 0 0-16 0"
    ) }

    val Scale: ImageVector by lazy { icon("Scale",
        "m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z",
        "m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z",
        "M7 21h10",
        "M12 3v18",
        "M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"
    ) }

    val QrCode: ImageVector by lazy { icon("QrCode",
        "M4 3h3a1 1 0 0 1 1 1v3a1 1 0 0 1 -1 1h-3a1 1 0 0 1 -1 -1v-3a1 1 0 0 1 1 -1Z",
        "M17 3h3a1 1 0 0 1 1 1v3a1 1 0 0 1 -1 1h-3a1 1 0 0 1 -1 -1v-3a1 1 0 0 1 1 -1Z",
        "M4 16h3a1 1 0 0 1 1 1v3a1 1 0 0 1 -1 1h-3a1 1 0 0 1 -1 -1v-3a1 1 0 0 1 1 -1Z",
        "M21 16h-3a2 2 0 0 0-2 2v3",
        "M21 21v.01",
        "M12 7v3a2 2 0 0 1-2 2H7",
        "M3 12h.01",
        "M12 3h.01",
        "M12 16v.01",
        "M16 12h1",
        "M21 12v.01",
        "M12 21v-1"
    ) }

    val ArrowDownToLine: ImageVector by lazy { icon("ArrowDownToLine",
        "M12 17V3",
        "m6 11 6 6 6-6",
        "M19 21H5"
    ) }

    val Leaf: ImageVector by lazy { icon("Leaf",
        "M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z",
        "M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"
    ) }

    val Sprout: ImageVector by lazy { icon("Sprout",
        "M7 20h10",
        "M10 20c5.5-2.5.8-6.4 3-10",
        "M9.5 9.4c1.1.8 1.8 2.2 2.3 3.7-2 .4-3.5.4-4.8-.3-1.2-.6-2.3-1.9-3-4.2 2.8-.5 4.4 0 5.5.8z",
        "M14.1 6a7 7 0 0 0-1.1 4c1.9-.1 3.3-.6 4.3-1.4 1-1 1.6-2.3 1.7-4.6-2.7.1-4 1-4.9 2z"
    ) }

    val MapPin: ImageVector by lazy { icon("MapPin",
        "M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0",
        "M9 10a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"
    ) }

    val LogOut: ImageVector by lazy { icon("LogOut",
        "M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4",
        "M16 17L21 12L16 7",
        "M21 12L9 12"
    ) }

    val RefreshCw: ImageVector by lazy { icon("RefreshCw",
        "M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8",
        "M21 3v5h-5",
        "M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16",
        "M8 16H3v5"
    ) }

    val TriangleAlert: ImageVector by lazy { icon("TriangleAlert",
        "m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3",
        "M12 9v4",
        "M12 17h.01"
    ) }

    val ArrowDownLeft: ImageVector by lazy { icon("ArrowDownLeft",
        "M17 7 7 17",
        "M17 17H7V7"
    ) }

    val ArrowUpRight: ImageVector by lazy { icon("ArrowUpRight",
        "M7 7h10v10",
        "M7 17 17 7"
    ) }

    val ArrowUpFromLine: ImageVector by lazy { icon("ArrowUpFromLine",
        "m18 9-6-6-6 6",
        "M12 3v14",
        "M5 21h14"
    ) }

    val Mail: ImageVector by lazy { icon("Mail",
        "M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-16a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2Z",
        "m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"
    ) }
}
