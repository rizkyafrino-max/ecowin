package id.ecowin.app

import java.text.NumberFormat
import java.time.OffsetDateTime
import java.time.ZoneId
import java.time.format.DateTimeFormatter
import java.util.Locale

private val localeId = Locale("id", "ID")

fun rupiah(value: Number?): String =
    "Rp " + NumberFormat.getNumberInstance(localeId).apply { maximumFractionDigits = 0 }.format(value ?: 0)

fun kg(value: Double?): String =
    NumberFormat.getNumberInstance(localeId).apply { maximumFractionDigits = 2 }.format(value ?: 0.0) + " kg"

fun tanggal(iso: String?, withTime: Boolean = true): String {
    if (iso.isNullOrBlank()) return "-"
    return runCatching {
        val pattern = if (withTime) "d MMM yyyy, HH:mm" else "d MMM yyyy"
        OffsetDateTime.parse(iso).atZoneSameInstant(ZoneId.systemDefault())
            .format(DateTimeFormatter.ofPattern(pattern, localeId))
    }.getOrElse { iso.take(10) }
}

fun statusLabel(status: String?): String = when (status) {
    "pending" -> "Menunggu"
    "approved" -> "Disetujui"
    "rejected" -> "Ditolak"
    "completed" -> "Selesai"
    "belum_panen" -> "Belum panen"
    "siap_panen" -> "Siap panen"
    "sudah_panen" -> "Sudah panen"
    "diproses" -> "Diproses"
    "selesai" -> "Selesai"
    else -> status ?: "-"
}

/** Inisial untuk avatar: huruf pertama nama (sama dengan Web). */
fun inisial(nama: String?): String = nama?.trim()?.firstOrNull()?.uppercase() ?: "?"

/**
 * URL foto profil yang aman dimuat: wajib https dan host milik Google (googleusercontent.com).
 * Nilai lain (http, host asing, skema aneh) ditolak agar aplikasi tidak memuat sumber sembarang.
 */
fun safeAvatarUrl(url: String?): String? {
    if (url.isNullOrBlank() || url.length > 512) return null
    val uri = runCatching { java.net.URI(url.trim()) }.getOrNull() ?: return null
    val host = uri.host?.lowercase() ?: return null
    if (uri.scheme != "https" || uri.userInfo != null) return null
    if (host != "googleusercontent.com" && !host.endsWith(".googleusercontent.com")) return null
    return uri.toString()
}
