package id.ecowin.app

import android.content.Context
import com.google.mlkit.vision.barcode.common.Barcode
import com.google.mlkit.vision.codescanner.GmsBarcodeScannerOptions
import com.google.mlkit.vision.codescanner.GmsBarcodeScanning

/**
 * Membuka pemindai QR bawaan Google Play Services (layar sendiri dengan kamera). Hanya format QR diterima.
 * [onHasil] menerima isi mentah QR; [onGagal] menerima pesan ramah (pemindaian dibatalkan tidak dianggap gagal).
 */
fun mulaiScanQr(context: Context, onHasil: (String) -> Unit, onGagal: (String) -> Unit) {
    val opsi = GmsBarcodeScannerOptions.Builder().setBarcodeFormats(Barcode.FORMAT_QR_CODE).build()
    GmsBarcodeScanning.getClient(context, opsi).startScan()
        .addOnSuccessListener { hasil -> hasil.rawValue?.let(onHasil) ?: onGagal("QR tidak terbaca. Coba lagi.") }
        .addOnFailureListener { e ->
            val batal = e.message.orEmpty().contains("cancel", ignoreCase = true)
            if (!batal) onGagal("Pemindai QR belum bisa dibuka. Pastikan Google Play Services aktif, lalu coba lagi.")
        }
}
