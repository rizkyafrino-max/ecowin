package id.ecowin.app

import android.content.Context
import android.hardware.Sensor
import android.hardware.SensorEvent
import android.hardware.SensorEventListener
import android.hardware.SensorManager
import android.os.SystemClock
import androidx.compose.runtime.Composable
import androidx.compose.runtime.DisposableEffect
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.rememberUpdatedState
import androidx.compose.ui.platform.LocalContext
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.LifecycleEventObserver
import androidx.lifecycle.compose.LocalLifecycleOwner
import kotlin.math.sqrt

/**
 * Logika "goyang" yang murni (tanpa Android) agar bisa diuji: guncangan dihitung bila gaya melewati [ambang] (satuan g,
 * HP diam = 1 g), minimal [butuh] kali dalam [jendelaMs], dengan jarak antarpuncak minimal [jedaMinMs] supaya satu
 * hentakan panjang tidak dihitung berkali-kali. Setelah terpicu ada masa tenang [redamMs] agar halaman tidak terbuka berulang.
 */
class PenghitungGoyang(
    private val ambang: Float = 2.7f,
    private val jedaMinMs: Long = 120,
    private val jendelaMs: Long = 1_200,
    private val butuh: Int = 3,
    private val redamMs: Long = 3_000,
) {
    private val puncak = ArrayDeque<Long>()
    private var terakhirPicu = Long.MIN_VALUE / 2

    /** @return true tepat saat guncangan dinyatakan terjadi. */
    fun masuk(gaya: Float, waktuMs: Long): Boolean {
        if (waktuMs - terakhirPicu < redamMs) return false
        if (gaya < ambang) return false
        if (puncak.isNotEmpty() && waktuMs - puncak.last() < jedaMinMs) return false
        puncak.addLast(waktuMs)
        while (puncak.isNotEmpty() && waktuMs - puncak.first() > jendelaMs) puncak.removeFirst()
        if (puncak.size < butuh) return false
        puncak.clear()
        terakhirPicu = waktuMs
        return true
    }
}

/** Pengaturan aplikasi (bukan notifikasi). Fitur goyang menyala secara bawaan tetapi bisa dimatikan di Pengaturan. */
object PengaturanApp {
    private const val PREFS = "ecowin_pengaturan"
    private const val KEY_GOYANG = "goyang_lapor_bug"

    val goyangAktif = mutableStateOf(true)
    private var dimuat = false

    private fun prefs(c: Context) = c.applicationContext.getSharedPreferences(PREFS, Context.MODE_PRIVATE)

    fun muat(c: Context) {
        if (dimuat) return
        goyangAktif.value = prefs(c).getBoolean(KEY_GOYANG, true)
        dimuat = true
    }

    fun setGoyang(c: Context, nilai: Boolean) {
        prefs(c).edit().putBoolean(KEY_GOYANG, nilai).apply()
        goyangAktif.value = nilai
    }
}

/**
 * Mendengarkan akselerometer hanya saat aplikasi terlihat di layar (didaftarkan saat START, dilepas saat STOP), sehingga tidak
 * ada pemakaian baterai di latar belakang dan tidak ada pembacaan sensor saat aplikasi tertutup.
 */
@Composable
fun GoyangUntukLaporBug(aktif: Boolean, onGoyang: () -> Unit) {
    val konteks = LocalContext.current
    val lifecycleOwner = LocalLifecycleOwner.current
    val terbaru = rememberUpdatedState(onGoyang)
    DisposableEffect(aktif, lifecycleOwner) {
        val sm = konteks.getSystemService(Context.SENSOR_SERVICE) as? SensorManager
        val sensor = sm?.getDefaultSensor(Sensor.TYPE_ACCELEROMETER)
        if (!aktif || sm == null || sensor == null) return@DisposableEffect onDispose { }

        val hitung = PenghitungGoyang()
        val pendengar = object : SensorEventListener {
            override fun onSensorChanged(e: SensorEvent) {
                val g = sqrt(e.values[0] * e.values[0] + e.values[1] * e.values[1] + e.values[2] * e.values[2]) / SensorManager.GRAVITY_EARTH
                if (hitung.masuk(g, SystemClock.elapsedRealtime())) terbaru.value()
            }

            override fun onAccuracyChanged(sensor: Sensor?, accuracy: Int) = Unit
        }
        val pengamat = LifecycleEventObserver { _, peristiwa ->
            when (peristiwa) {
                Lifecycle.Event.ON_START -> sm.registerListener(pendengar, sensor, SensorManager.SENSOR_DELAY_GAME)
                Lifecycle.Event.ON_STOP -> sm.unregisterListener(pendengar)
                else -> Unit
            }
        }
        lifecycleOwner.lifecycle.addObserver(pengamat)
        onDispose {
            lifecycleOwner.lifecycle.removeObserver(pengamat)
            sm.unregisterListener(pendengar)
        }
    }
}
