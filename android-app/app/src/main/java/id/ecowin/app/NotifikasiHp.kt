package id.ecowin.app

import android.Manifest
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import androidx.core.content.ContextCompat
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.ProcessLifecycleOwner
import androidx.work.Constraints
import androidx.work.CoroutineWorker
import androidx.work.ExistingPeriodicWorkPolicy
import androidx.work.NetworkType
import androidx.work.PeriodicWorkRequestBuilder
import androidx.work.WorkManager
import androidx.work.WorkerParameters
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import retrofit2.HttpException
import java.io.IOException
import java.util.concurrent.TimeUnit

/** Hasil ringkasan notifikasi baru yang belum pernah diberitahukan ke HP. */
internal data class RingkasanNotif(val jumlah: Int, val terbaru: NotifikasiDto, val idTertinggi: Long)

/** Notifikasi yang belum dibaca dan lebih baru dari [idTerakhir]; null bila tidak ada. */
internal fun ringkasBaru(daftar: List<NotifikasiDto>, idTerakhir: Long): RingkasanNotif? {
    val baru = daftar.filter { !it.dibaca && it.id > idTerakhir }
    val terbaru = baru.maxByOrNull { it.id } ?: return null
    return RingkasanNotif(baru.size, terbaru, terbaru.id)
}

/** Permintaan membuka layar Notifikasi dari ketukan pemberitahuan (hanya berlaku bila pengguna sudah login). */
object NavAwal {
    var permintaan: Boolean by mutableStateOf(false)
}

/**
 * Pemberitahuan di HP (tanpa layanan pihak ketiga): pengecekan berkala ke server saat ada koneksi.
 *
 * Aspek keamanan:
 *  - Opt-in: mati sampai nasabah menyalakannya sendiri (dan memberi izin notifikasi Android 13+).
 *  - Layar kunci hanya menampilkan teks generik; judul/nominal baru terlihat setelah HP dibuka (VISIBILITY_PRIVATE).
 *  - Terikat ke sesi: tanpa token tersimpan tidak ada yang dikirim; logout/sesi berakhir menghentikan pekerjaan,
 *    menghapus pemberitahuan, dan mematikan opt-in (akun berikutnya di HP yang sama harus menyalakannya lagi).
 *  - Tidak memberi notifikasi saat aplikasi sedang terbuka, tidak mencatat isi pesan ke log.
 *  - Ketukan hanya membuka layar Notifikasi lewat PendingIntent eksplisit & immutable, tanpa data dalam intent.
 */
object NotifikasiHp {
    const val AKSI_BUKA = "id.ecowin.app.BUKA_NOTIFIKASI"
    private const val KANAL = "ecowin_kabar"
    private const val ID_NOTIF = 1001
    private const val KERJA = "ecowin-notifikasi"
    private const val PREFS = "ecowin_notif_hp"
    private const val KEY_AKTIF = "aktif"
    private const val KEY_NADA = "nada"
    private const val KEY_JADWAL_V2 = "jadwal_v2"

    private fun prefs(c: Context) = c.applicationContext.getSharedPreferences(PREFS, Context.MODE_PRIVATE)

    fun aktif(c: Context): Boolean = prefs(c).getBoolean(KEY_AKTIF, false)

    fun izinDiberikan(c: Context): Boolean =
        Build.VERSION.SDK_INT < 33 || ContextCompat.checkSelfPermission(c, Manifest.permission.POST_NOTIFICATIONS) == PackageManager.PERMISSION_GRANTED

    fun nyalakan(c: Context) {
        prefs(c).edit().putBoolean(KEY_AKTIF, true).apply()
        jadwalkan(c)
    }

    /** Mematikan total: opt-in, jadwal, dan pemberitahuan yang sedang tampil. Dipanggil saat logout/sesi berakhir. */
    fun berhenti(c: Context) {
        prefs(c).edit().putBoolean(KEY_AKTIF, false).apply()
        Push.matikanLokal(c)
        WorkManager.getInstance(c.applicationContext).cancelUniqueWork(KERJA)
        NotificationManagerCompat.from(c.applicationContext).cancel(ID_NOTIF)
    }

    /** Jadwal ulang sekali setelah perbaikan backoff: pekerjaan lama yang terlanjur tertunda berjam-jam ikut dibersihkan. */
    fun perluJadwalUlang(c: Context): Boolean {
        val p = prefs(c)
        if (p.getBoolean(KEY_JADWAL_V2, false)) return false
        p.edit().putBoolean(KEY_JADWAL_V2, true).apply()
        return true
    }

    fun jadwalkan(c: Context, ulang: Boolean = false) {
        val syarat = Constraints.Builder().setRequiredNetworkType(NetworkType.CONNECTED).build()
        val kerja = PeriodicWorkRequestBuilder<NotifikasiWorker>(15, TimeUnit.MINUTES).setConstraints(syarat).build()
        WorkManager.getInstance(c.applicationContext).enqueueUniquePeriodicWork(KERJA, if (ulang) ExistingPeriodicWorkPolicy.CANCEL_AND_REENQUEUE else ExistingPeriodicWorkPolicy.KEEP, kerja)
    }

    /** Nada notifikasi pilihan nasabah. Kanal Android tidak bisa diganti suaranya setelah dibuat, jadi tiap nada punya kanal sendiri. */
    enum class Nada(val kode: String, val label: String, val raw: Int?) {
        Ecowin("ecowin", "EcoWin", R.raw.nada_ecowin),
        Daun("daun", "Daun", R.raw.nada_daun),
        Koin("koin", "Koin", R.raw.nada_koin),
        Sistem("sistem", "Bawaan HP", null),
    }

    fun nada(c: Context): Nada = Nada.entries.firstOrNull { it.kode == prefs(c).getString(KEY_NADA, null) } ?: Nada.Ecowin

    /** Menyimpan pilihan nada dan langsung menyiapkan kanalnya (kanal nada lain dihapus agar pengaturan HP tetap rapi). */
    fun setNada(c: Context, n: Nada) {
        prefs(c).edit().putString(KEY_NADA, n.kode).apply()
        siapkanKanal(c.applicationContext)
    }

    private fun siapkanKanal(app: Context): String {
        val n = nada(app)
        val id = "${KANAL}_${n.kode}"
        if (Build.VERSION.SDK_INT < 26) return id
        val nm = app.getSystemService(NotificationManager::class.java)
        nm.notificationChannels.filter { it.id.startsWith(KANAL) && it.id != id }.forEach { nm.deleteNotificationChannel(it.id) }
        if (nm.getNotificationChannel(id) == null) {
            val kanal = NotificationChannel(id, "Kabar EcoWin", NotificationManager.IMPORTANCE_DEFAULT).apply {
                description = "Setoran, penarikan, dan aktivitas akun Anda."
                lockscreenVisibility = android.app.Notification.VISIBILITY_PRIVATE
                setShowBadge(true)
                val uri = n.raw?.let { android.net.Uri.parse("android.resource://${app.packageName}/$it") } ?: android.media.RingtoneManager.getDefaultUri(android.media.RingtoneManager.TYPE_NOTIFICATION)
                setSound(uri, android.media.AudioAttributes.Builder().setUsage(android.media.AudioAttributes.USAGE_NOTIFICATION).setContentType(android.media.AudioAttributes.CONTENT_TYPE_SONIFICATION).build())
            }
            nm.createNotificationChannel(kanal)
        }
        return id
    }

    /** Memutar contoh nada saat nasabah memilih (hormati volume notifikasi; tidak memutar bila HP senyap). */
    fun pratinjau(c: Context, n: Nada) {
        val app = c.applicationContext
        val attr = android.media.AudioAttributes.Builder().setUsage(android.media.AudioAttributes.USAGE_NOTIFICATION).setContentType(android.media.AudioAttributes.CONTENT_TYPE_SONIFICATION).build()
        runCatching {
            if (n.raw != null) {
                android.media.MediaPlayer.create(app, n.raw, attr, android.media.AudioManager.AUDIO_SESSION_ID_GENERATE)?.apply { setOnCompletionListener { it.release() }; start() }
            } else {
                android.media.RingtoneManager.getRingtone(app, android.media.RingtoneManager.getDefaultUri(android.media.RingtoneManager.TYPE_NOTIFICATION))?.play()
            }
        }
    }

    internal fun tampilkan(c: Context, r: RingkasanNotif) {
        val app = c.applicationContext
        if (!izinDiberikan(app) || !NotificationManagerCompat.from(app).areNotificationsEnabled()) return
        val kanal = siapkanKanal(app)
        val buka = Intent(app, MainActivity::class.java).setAction(AKSI_BUKA).addFlags(Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP)
        val tujuan = PendingIntent.getActivity(app, 0, buka, PendingIntent.FLAG_IMMUTABLE or PendingIntent.FLAG_UPDATE_CURRENT)
        val umum = NotificationCompat.Builder(app, kanal)
            .setSmallIcon(R.drawable.ic_notif)
            .setContentTitle("EcoWin")
            .setContentText("Ada kabar baru. Buka kunci HP untuk melihat.")
            .build()
        val judul = if (r.jumlah == 1) r.terbaru.judul else "${r.jumlah} kabar baru di EcoWin"
        val isi = if (r.jumlah == 1) r.terbaru.isi else "Ketuk untuk melihat semuanya."
        val notif = NotificationCompat.Builder(app, kanal)
            .setSmallIcon(R.drawable.ic_notif)
            .setColor(0xFF059669.toInt())
            .setLargeIcon(android.graphics.BitmapFactory.decodeResource(app.resources, R.mipmap.ic_launcher))
            .setContentTitle(judul)
            .setContentText(isi)
            .setStyle(NotificationCompat.BigTextStyle().bigText(isi))
            .setVisibility(NotificationCompat.VISIBILITY_PRIVATE)
            .setPublicVersion(umum)
            .setContentIntent(tujuan)
            .setAutoCancel(true)
            .setOnlyAlertOnce(true)
            .build()
        runCatching { NotificationManagerCompat.from(app).notify(ID_NOTIF, notif) }
    }
}

/** Log status saja (tanpa isi pesan) dan hanya di build debug. */
private fun catat(status: String) { if (BuildConfig.DEBUG) android.util.Log.i("EcoWinNotif", status) }

/**
 * Mengecek notifikasi baru ke server dan menampilkannya. Dipakai pengecek berkala (WorkManager) dan ping FCM.
 * Tidak melakukan apa pun tanpa sesi, tanpa opt-in, atau saat aplikasi terbuka. Tidak pernah melempar galat jaringan:
 * bila gagal, putaran/ping berikutnya yang mencoba lagi (retry WorkManager membuat jeda membengkak sampai 5 jam).
 */
internal suspend fun periksaNotifikasi(c: Context, paksa: Boolean = false) {
    val session = SessionStore(c)
    if (!session.hasSession() || !NotifikasiHp.aktif(c)) {
        catat("berhenti: sesi=${session.hasSession()} aktif=${NotifikasiHp.aktif(c)}")
        NotifikasiHp.berhenti(c)
        return
    }
    val terlihat = !paksa && withContext(Dispatchers.Main) { ProcessLifecycleOwner.get().lifecycle.currentState.isAtLeast(Lifecycle.State.STARTED) }
    if (terlihat) { catat("dilewati: aplikasi sedang terlihat"); return } // lonceng di dalam aplikasi sudah cukup

    val respons = try {
        ApiClient(session).api.notifikasi()
    } catch (e: HttpException) {
        catat("http ${e.code()}")
        if (e.code() == 401 || e.code() == 403) NotifikasiHp.berhenti(c)
        return
    } catch (e: IOException) {
        catat("jaringan: ${e.javaClass.simpleName}")
        return
    }
    if (!session.hasSession()) { NotifikasiHp.berhenti(c); return } // sesi dicabut saat refresh token

    val terakhir = session.notifTerakhir()
    catat("cek: ${respons.data.size} notifikasi, acuan=$terakhir")
    if (terakhir < 0) { // pengecekan pertama: jadikan acuan, jangan membanjiri dengan kabar lama
        session.setNotifTerakhir(respons.data.maxOfOrNull { it.id } ?: 0)
        return
    }
    ringkasBaru(respons.data, terakhir)?.let {
        catat("menampilkan ${it.jumlah} kabar baru")
        NotifikasiHp.tampilkan(c, it)
        session.setNotifTerakhir(it.idTertinggi)
    }
}

/** Pengecek berkala (cadangan bila FCM belum disiapkan atau tertunda oleh sistem). */
class NotifikasiWorker(context: Context, params: WorkerParameters) : CoroutineWorker(context, params) {
    companion object {
        const val EXTRA_PAKSA = "paksa"
    }

    override suspend fun doWork(): Result {
        val paksa = BuildConfig.DEBUG && inputData.getBoolean(EXTRA_PAKSA, false) // hanya untuk uji di build debug
        periksaNotifikasi(applicationContext, paksa)
        return Result.success()
    }
}

/** Setelah aplikasi diperbarui, jadwal pengecek dibuat ulang (tanpa perlu membuka aplikasi) agar tidak terjebak jadwal lama. */
class JadwalUlangReceiver : android.content.BroadcastReceiver() {
    override fun onReceive(context: Context, intent: Intent) {
        if (intent.action != Intent.ACTION_MY_PACKAGE_REPLACED) return
        if (NotifikasiHp.aktif(context) && SessionStore(context).hasSession()) NotifikasiHp.jadwalkan(context, ulang = true)
    }
}
