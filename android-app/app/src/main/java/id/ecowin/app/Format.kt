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
fun safeAvatarUrl(url: String?, apiBase: String = BuildConfig.API_BASE_URL): String? {
    if (url.isNullOrBlank() || url.length > 512) return null
    val uri = runCatching { java.net.URI(url.trim()) }.getOrNull() ?: return null
    if (uri.userInfo != null) return null
    val host = uri.host?.lowercase() ?: return null
    if (uri.scheme == "https" && (host == "googleusercontent.com" || host.endsWith(".googleusercontent.com"))) return uri.toString()
    return avatarServerEcoWin(uri, apiBase)
}

private val JalurAvatarServer = Regex("^/(foto/[A-Za-z0-9]{32}|images/avatar-(laki|perempuan)\\.jpg)$")

/**
 * Foto profil dari server EcoWin (foto unggahan atau avatar bawaan). Hanya jalur yang dikenal yang diterima, tanpa query/fragmen,
 * dan alamat asalnya SELALU dibentuk ulang dari alamat API aplikasi sendiri: host dalam data dari server tidak pernah dipakai,
 * sehingga tidak bisa mengarahkan aplikasi memuat gambar dari tempat lain.
 */
private fun avatarServerEcoWin(uri: java.net.URI, apiBase: String): String? {
    if (uri.rawQuery != null || uri.rawFragment != null) return null
    val jalur = uri.rawPath ?: return null
    if (!JalurAvatarServer.matches(jalur)) return null
    val dasar = runCatching { java.net.URI(apiBase.trim()) }.getOrNull() ?: return null
    val skema = dasar.scheme?.lowercase()
    if (skema != "https" && skema != "http") return null
    val otoritas = dasar.rawAuthority ?: return null
    return "$skema://$otoritas$jalur"
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

/**
 * Nama tampilan dari ID token Google, hanya untuk mengisi otomatis kolom nama di formulir daftar.
 * Token TIDAK diverifikasi di sini (server yang memverifikasi tanda tangan, audience, dan email); hasilnya hanya saran
 * isian yang bisa diubah pengguna, bukan dasar keputusan apa pun. Mengembalikan null bila bentuk token tidak sesuai.
 */
fun namaDariTokenGoogle(idToken: String?): String? {
    if (idToken.isNullOrBlank() || idToken.length > 4096) return null
    val bagian = idToken.split('.')
    if (bagian.size != 3) return null
    return runCatching {
        val json = String(java.util.Base64.getUrlDecoder().decode(bagian[1]), Charsets.UTF_8)
        val nama = com.google.gson.JsonParser.parseString(json).asJsonObject.get("name")?.asString
        nama?.replace(Regex("[\\p{Cntrl}]"), " ")?.trim()?.take(150)?.takeIf { it.length >= 2 }
    }.getOrNull()
}
