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
    "aktif" -> "Aktif"
    "belum_panen" -> "Belum dipanen"
    "siap_panen" -> "Siap panen"
    "sudah_panen" -> "Sudah dipanen"
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

/**
 * Isian angka desimal gaya Indonesia (sama dengan Web dan panel Admin): titik menjadi koma, hanya angka,
 * satu koma, dan diawali "0" + angka langsung berkoma ("05" menjadi "0,5").
 */
fun autoKoma(raw: String, maksDesimal: Int = 2): String {
    val s = raw.replace('.', ',').filter { it.isDigit() || it == ',' }
    val i = s.indexOf(',')
    var bulat = if (i == -1) s else s.substring(0, i)
    var pecah: String? = if (i == -1) null else s.substring(i + 1).replace(",", "")
    if (pecah == null && bulat.length >= 2 && bulat[0] == '0') {
        pecah = bulat.substring(1)
        bulat = "0"
    }
    if (bulat.length > 1) bulat = bulat.trimStart('0').ifEmpty { "0" }
    return if (pecah == null) bulat else bulat + "," + pecah.take(maksDesimal)
}

/** Teks berkoma ("0,5") menjadi Double; null bila tidak valid. */
fun keDouble(teks: String): Double? = teks.replace(',', '.').toDoubleOrNull()

/** Bilangan bulat (mis. rupiah): hanya angka dan tanpa nol di depan. */
fun soloAngka(raw: String, maksDigit: Int = 9): String {
    val d = raw.filter { it.isDigit() }.take(maksDigit)
    return if (d.length > 1) d.trimStart('0').ifEmpty { "0" } else d
}
