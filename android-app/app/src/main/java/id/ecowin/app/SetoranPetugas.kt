package id.ecowin.app

/** Isi QR Card nasabah hanya token acak 32-64 huruf/angka (sama dengan aturan server); selain itu ditolak sebelum dikirim. */
fun tokenQrValid(isi: String?): Boolean = isi != null && Regex("^[A-Za-z0-9]{32,64}$").matches(isi.trim())

/** Nasabah boleh disetori bila akunnya aktif dan sudah diverifikasi (server menegakkan aturan yang sama). */
fun bolehDisetori(n: NasabahDto): Boolean = n.status == "aktif" && n.statusVerifikasi == "verified"

/** Berat setoran: lebih dari 0 dan paling banyak 10.000 kg, sama dengan batas server. Koma dipakai sebagai desimal. */
fun beratSetoranValid(teks: String): Boolean {
    val b = keDouble(teks) ?: return false
    return b > 0 && b <= 10000
}
