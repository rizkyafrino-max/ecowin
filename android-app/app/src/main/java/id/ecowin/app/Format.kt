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
