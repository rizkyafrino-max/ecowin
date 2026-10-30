package id.ecowin.app

import android.content.Context
import android.graphics.Bitmap
import android.graphics.BitmapFactory
import android.graphics.Canvas
import android.graphics.Paint
import android.graphics.Rect
import android.os.SystemClock
import android.provider.Settings
import android.util.AttributeSet
import android.view.View

/**
 * Pipa BioporiPrint berputar 360°: lembar sprite 6x6 (36 frame, dirender dari model 3D) yang digeser per frame.
 * Ringan (satu WebP ~120 KB, tanpa pustaka 3D). Hanya menganimasikan saat tampil di layar, dan diam pada frame pertama
 * bila pengguna mematikan animasi sistem.
 */
class PipaPutarView @JvmOverloads constructor(context: Context, attrs: AttributeSet? = null) : View(context, attrs) {
    private val paint = Paint(Paint.FILTER_BITMAP_FLAG or Paint.ANTI_ALIAS_FLAG)
    private val sumber = Rect()
    private val tujuan = Rect()
    private val animasiMati = Settings.Global.getFloat(context.contentResolver, Settings.Global.ANIMATOR_DURATION_SCALE, 1f) == 0f
    private val mulai = SystemClock.uptimeMillis()

    init {
        contentDescription = "Model 3D pipa BioporiPrint yang berputar"
        importantForAccessibility = IMPORTANT_FOR_ACCESSIBILITY_NO // dekoratif; informasi lokasi ada di teks
    }

    private val tick = object : Runnable {
        override fun run() {
            invalidate()
            postDelayed(this, FRAME_MS)
        }
    }

    override fun onAttachedToWindow() {
        super.onAttachedToWindow()
        if (!animasiMati) post(tick)
    }

    override fun onDetachedFromWindow() {
        removeCallbacks(tick)
        super.onDetachedFromWindow()
    }

    override fun onDraw(canvas: Canvas) {
        val lembar = lembar(context) ?: return
        val frame = if (animasiMati) 0 else (((SystemClock.uptimeMillis() - mulai) / FRAME_MS) % FRAME).toInt()
        val lebarF = lembar.width / KOLOM
        val tinggiF = lembar.height / KOLOM
        val kol = frame % KOLOM
        val baris = frame / KOLOM
        sumber.set(kol * lebarF, baris * tinggiF, (kol + 1) * lebarF, (baris + 1) * tinggiF)
        tujuan.set(0, 0, width, height)
        canvas.drawBitmap(lembar, sumber, tujuan, paint)
    }

    companion object {
        private const val KOLOM = 6
        private const val FRAME = 36
        private const val FRAME_MS = 83L // ~12 fps
        private var cache: Bitmap? = null

        /** Lembar sprite dimuat sekali dan dipakai bersama semua popup. */
        @Synchronized
        private fun lembar(context: Context): Bitmap? {
            cache?.let { return it }
            val opsi = BitmapFactory.Options().apply { inScaled = false }
            return BitmapFactory.decodeResource(context.resources, R.drawable.biopori_putar, opsi).also { cache = it }
        }
    }
}
