package id.ecowin.app

import android.content.Context
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.graphics.Matrix
import android.net.Uri
import androidx.core.content.FileProvider
import androidx.exifinterface.media.ExifInterface
import java.io.File
import java.util.UUID

/**
 * Foto bukti Biopori: diambil dari kamera, diputar sesuai EXIF, diperkecil
 * (maks 1600 px) dan dikompresi JPEG sebelum diunggah. Metadata (termasuk GPS)
 * tidak ikut terkirim karena gambar di-encode ulang.
 */
object PhotoUtils {

    private const val MAX_SIDE = 1600
    private const val QUALITY = 80

    fun newCameraUri(context: Context): Uri {
        val dir = File(context.cacheDir, "foto").apply { mkdirs() }
        val file = File(dir, "biopori-${UUID.randomUUID()}.jpg")
        return FileProvider.getUriForFile(context, "${context.packageName}.fileprovider", file)
    }

    fun compress(context: Context, source: Uri): File {
        val resolver = context.contentResolver

        val bounds = BitmapFactory.Options().apply { inJustDecodeBounds = true }
        resolver.openInputStream(source)?.use { BitmapFactory.decodeStream(it, null, bounds) }
        require(bounds.outWidth > 0 && bounds.outHeight > 0) { "File bukan gambar yang valid." }

        var sample = 1
        while (maxOf(bounds.outWidth, bounds.outHeight) / (sample * 2) >= MAX_SIDE) sample *= 2

        val bitmap = resolver.openInputStream(source)?.use {
            BitmapFactory.decodeStream(it, null, BitmapFactory.Options().apply { inSampleSize = sample })
        } ?: error("Gagal membaca foto.")

        val rotation = resolver.openInputStream(source)?.use {
            when (ExifInterface(it).getAttributeInt(ExifInterface.TAG_ORIENTATION, ExifInterface.ORIENTATION_NORMAL)) {
                ExifInterface.ORIENTATION_ROTATE_90 -> 90f
                ExifInterface.ORIENTATION_ROTATE_180 -> 180f
                ExifInterface.ORIENTATION_ROTATE_270 -> 270f
                else -> 0f
            }
        } ?: 0f

        val scale = minOf(1f, MAX_SIDE.toFloat() / maxOf(bitmap.width, bitmap.height))
        val matrix = Matrix().apply {
            postRotate(rotation)
            postScale(scale, scale)
        }
        val output = Bitmap.createBitmap(bitmap, 0, 0, bitmap.width, bitmap.height, matrix, true)

        val target = File(context.cacheDir, "foto/upload-${UUID.randomUUID()}.jpg")
        target.outputStream().use { output.compress(Bitmap.CompressFormat.JPEG, QUALITY, it) }
        if (output != bitmap) output.recycle()
        bitmap.recycle()

        return target
    }

    fun cleanup(context: Context) {
        File(context.cacheDir, "foto").listFiles()?.forEach { it.delete() }
    }
}
