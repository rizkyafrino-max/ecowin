package id.ecowin.app

import android.content.ContentValues
import android.content.Context
import android.content.Intent
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.graphics.Canvas
import android.graphics.Color
import android.graphics.LinearGradient
import android.graphics.Paint
import android.graphics.Path
import android.graphics.RectF
import android.graphics.Shader
import android.graphics.Typeface
import android.os.Build
import android.os.Environment
import android.provider.MediaStore
import androidx.core.content.FileProvider
import androidx.core.content.res.ResourcesCompat
import androidx.core.graphics.PathParser
import java.io.File

/**
 * Menggambar Kartu Nasabah menjadi gambar PNG (1712 x 1080 px) dengan algoritma dan proporsi yang sama dengan penggambar di web dan
 * panel (web/src/utils/kartuPng.js), lalu menyimpannya ke galeri atau membagikannya. Hasilnya sama persis dengan tampilan kartu.
 */
object KartuGambar {
    const val LEBAR = 1712
    const val TINGGI = 1080
    private const val PAD = 111f

    /** Nama berkas aman dari nomor nasabah: hanya huruf, angka, dan strip. */
    fun namaBerkas(nomor: String?): String = "Kartu-EcoWin-" + (nomor.orEmpty().filter { it.isLetterOrDigit() || it == '-' }.ifEmpty { "nasabah" }) + ".png"

    private fun kuas(warna: Int, ukuran: Float = 0f, tipe: Typeface? = null, berat: Int = 400, spasi: Float = 0f): Paint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
        color = warna
        if (ukuran > 0f) textSize = ukuran
        if (tipe != null) {
            typeface = tipe
            fontVariationSettings = "'wght' $berat" // Inter variabel: ketebalan diatur lewat sumbu wght (API 26+)
        }
        letterSpacing = if (ukuran > 0f) spasi / ukuran else 0f // dalam satuan em
    }

    /** Membungkus teks menjadi paling banyak [maksBaris] baris selebar [lebar]; baris terakhir diberi elipsis bila masih panjang. */
    internal fun bungkus(teks: String, lebar: Float, maksBaris: Int, ukur: (String) -> Float): List<String> {
        val baris = mutableListOf<String>()
        var kini = ""
        for (k in teks.trim().split(Regex("\\s+")).filter { it.isNotEmpty() }) {
            val coba = if (kini.isEmpty()) k else "$kini $k"
            if (ukur(coba) <= lebar || kini.isEmpty()) kini = coba else { baris += kini; kini = k }
        }
        if (kini.isNotEmpty()) baris += kini
        val sisa = baris.take(maksBaris).toMutableList()
        // Bila teks masih tersisa, baris terakhir diberi elipsis.
        val terpotong = baris.size > maksBaris
        // Setiap baris dijamin muat: satu kata yang lebih lebar dari kartu juga dipotong dengan elipsis.
        return sisa.mapIndexed { i, b -> potongMuat(b, lebar, ukur, paksaElipsis = terpotong && i == sisa.lastIndex) }
    }

    private fun potongMuat(teks: String, lebar: Float, ukur: (String) -> Float, paksaElipsis: Boolean): String {
        if (!paksaElipsis && ukur(teks) <= lebar) return teks
        var akhir = teks.trimEnd('…')
        while (akhir.length > 1 && ukur("$akhir…") > lebar) akhir = akhir.dropLast(1)
        return "$akhir…"
    }

    fun buat(context: Context, nama: String, nomor: String, bank: String, qr: Bitmap): Bitmap {
        val hasil = Bitmap.createBitmap(LEBAR, TINGGI, Bitmap.Config.ARGB_8888)
        val c = Canvas(hasil)
        val inter = ResourcesCompat.getFont(context, R.font.inter_variable) ?: Typeface.DEFAULT
        val emblem = BitmapFactory.decodeResource(context.resources, R.drawable.ecowin_emblem)
        val w = LEBAR.toFloat()
        val h = TINGGI.toFloat()

        c.save()
        c.clipPath(Path().apply { addRoundRect(RectF(0f, 0f, w, h), 60f, 60f, Path.Direction.CW) })

        // Latar gradasi hijau EcoWin
        c.drawRect(0f, 0f, w, h, Paint(Paint.ANTI_ALIAS_FLAG).apply {
            shader = LinearGradient(0f, 0f, w, h, intArrayOf(0xFF064E3B.toInt(), 0xFF059669.toInt(), 0xFF34D399.toInt()), floatArrayOf(0f, 0.5f, 1f), Shader.TileMode.CLAMP)
        })

        // Hiasan: lingkaran bening dan daun samar
        c.drawCircle(w - 60f, -50f, 330f, kuas(Color.argb(23, 255, 255, 255)))
        c.drawCircle(70f, h + 40f, 260f, kuas(Color.argb(18, 255, 255, 255)))
        c.save()
        c.translate(w - 580f, h - 560f)
        c.scale(22f, 22f)
        val garisDaun = Paint(Paint.ANTI_ALIAS_FLAG).apply { style = Paint.Style.STROKE; strokeWidth = 2f; strokeCap = Paint.Cap.ROUND; strokeJoin = Paint.Join.ROUND; color = Color.argb(18, 255, 255, 255) }
        c.drawPath(PathParser.createPathFromPathData("M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"), garisDaun)
        c.drawPath(PathParser.createPathFromPathData("M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"), garisDaun)
        c.restore()

        // Merek: lambang di kotak putih, nama, dan sub-judul
        val kotak = 171f
        c.drawRoundRect(RectF(PAD, PAD, PAD + kotak, PAD + kotak), 41f, 41f, kuas(Color.WHITE))
        val tinggiLambang = kotak - 40f
        val lebarLambang = tinggiLambang * emblem.width / emblem.height
        c.drawBitmap(emblem, null, RectF(PAD + (kotak - lebarLambang) / 2, PAD + 20f, PAD + (kotak + lebarLambang) / 2, PAD + 20f + tinggiLambang), Paint(Paint.FILTER_BITMAP_FLAG))
        val xMerek = PAD + kotak + 41f
        val kuasEco = kuas(Color.WHITE, 72f, inter, 800)
        c.drawText("Eco", xMerek, PAD + 74f, kuasEco)
        c.drawText("Win", xMerek + kuasEco.measureText("Eco"), PAD + 74f, kuas(0xFFA7F3D0.toInt(), 72f, inter, 800))
        c.drawText("BANK SAMPAH DIGITAL", xMerek, PAD + 132f, kuas(0xFFA7F3D0.toInt(), 30f, inter, 700, 3f))

        // Identitas nasabah
        val lebarTeks = 968f - 86f - PAD
        c.drawText("KARTU NASABAH", PAD, 440f, kuas(0xFFA7F3D0.toInt(), 39f, inter, 700, 6f))
        val kuasNama = kuas(Color.WHITE, 96f, inter, 800)
        val barisNama = bungkus(nama, lebarTeks, 2) { kuasNama.measureText(it) }
        barisNama.forEachIndexed { i, b -> c.drawText(b, PAD, 536f + i * 110f, kuasNama) }
        val kuasNomor = kuas(0xFFD1FAE5.toInt(), 62f, Typeface.create(Typeface.MONOSPACE, Typeface.BOLD), 600, 7f)
        c.drawText(nomor, PAD, 536f + barisNama.size * 110f + 30f, kuasNomor)

        // Bank sampah dengan ikon lokasi
        c.save()
        c.translate(PAD, h - PAD - 52f)
        c.scale(2.2f, 2.2f)
        val garisPin = Paint(Paint.ANTI_ALIAS_FLAG).apply { style = Paint.Style.STROKE; strokeWidth = 2f; strokeCap = Paint.Cap.ROUND; strokeJoin = Paint.Join.ROUND; color = 0xFFA7F3D0.toInt() }
        c.drawPath(PathParser.createPathFromPathData("M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"), garisPin)
        c.drawCircle(12f, 10f, 3f, garisPin)
        c.restore()
        val kuasBank = kuas(Color.argb(235, 255, 255, 255), 50f, inter, 500)
        c.drawText(bungkus(bank.ifBlank { "EcoWin" }, lebarTeks - 80f, 1) { kuasBank.measureText(it) }.firstOrNull().orEmpty(), PAD + 80f, h - PAD - 8f, kuasBank)

        // QR putih besar di kanan, tajam (tanpa penghalusan)
        val sisi = 633f
        val xQr = w - PAD - sisi
        val yQr = 193f
        c.drawRoundRect(RectF(xQr, yQr, xQr + sisi, yQr + sisi), 62f, 62f, kuas(Color.WHITE))
        c.drawBitmap(qr, null, RectF(xQr + 27f, yQr + 27f, xQr + sisi - 27f, yQr + sisi - 27f), Paint().apply { isFilterBitmap = false; isAntiAlias = false })
        val kuasPindai = kuas(0xFFD1FAE5.toInt(), 34f, inter, 700, 3f).apply { textAlign = Paint.Align.CENTER }
        c.drawText("PINDAI OLEH PETUGAS", xQr + sisi / 2, yQr + sisi + 62f, kuasPindai)

        c.restore()
        c.drawRoundRect(RectF(2f, 2f, w - 2f, h - 2f), 58f, 58f, Paint(Paint.ANTI_ALIAS_FLAG).apply { style = Paint.Style.STROKE; strokeWidth = 4f; color = Color.argb(64, 255, 255, 255) })
        emblem.recycle()
        return hasil
    }

    /** Menyimpan ke galeri (Pictures/EcoWin). Hanya Android 10+ karena tidak butuh izin penyimpanan; di bawah itu pakai [bagikan]. */
    fun simpanKeGaleri(context: Context, gambar: Bitmap, nomor: String?): Boolean {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.Q) return false
        val resolver = context.contentResolver
        val nilai = ContentValues().apply {
            put(MediaStore.Images.Media.DISPLAY_NAME, namaBerkas(nomor))
            put(MediaStore.Images.Media.MIME_TYPE, "image/png")
            put(MediaStore.Images.Media.RELATIVE_PATH, Environment.DIRECTORY_PICTURES + "/EcoWin")
            put(MediaStore.Images.Media.IS_PENDING, 1)
        }
        val uri = resolver.insert(MediaStore.Images.Media.EXTERNAL_CONTENT_URI, nilai) ?: return false
        return try {
            val tertulis = resolver.openOutputStream(uri)?.use { gambar.compress(Bitmap.CompressFormat.PNG, 100, it) } ?: false
            if (!tertulis) { resolver.delete(uri, null, null); return false }
            nilai.clear()
            nilai.put(MediaStore.Images.Media.IS_PENDING, 0)
            resolver.update(uri, nilai, null, null)
            true
        } catch (e: Exception) {
            resolver.delete(uri, null, null)
            false
        }
    }

    /** Membagikan gambar lewat lembar bagikan Android (WhatsApp, Drive, aplikasi cetak, dan lain-lain). */
    fun bagikan(context: Context, gambar: Bitmap, nomor: String?) {
        val folder = File(context.cacheDir, "foto").apply { mkdirs() } // sama dengan jalur FileProvider (file_paths.xml)
        val berkas = File(folder, namaBerkas(nomor))
        berkas.outputStream().use { gambar.compress(Bitmap.CompressFormat.PNG, 100, it) }
        val uri = FileProvider.getUriForFile(context, "${context.packageName}.fileprovider", berkas)
        val kirim = Intent(Intent.ACTION_SEND).apply {
            type = "image/png"
            putExtra(Intent.EXTRA_STREAM, uri)
            addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
        }
        context.startActivity(Intent.createChooser(kirim, "Bagikan Kartu Nasabah").addFlags(Intent.FLAG_ACTIVITY_NEW_TASK))
    }
}
