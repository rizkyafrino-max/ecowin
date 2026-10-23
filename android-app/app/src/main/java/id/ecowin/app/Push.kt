package id.ecowin.app

import android.content.Context
import com.google.firebase.FirebaseApp
import com.google.firebase.messaging.FirebaseMessaging
import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.runBlocking
import kotlinx.coroutines.suspendCancellableCoroutine
import kotlinx.coroutines.withContext
import kotlinx.coroutines.withTimeoutOrNull
import kotlin.coroutines.resume

/**
 * Firebase Cloud Messaging sebagai "ping" pembangun: pesan FCM hanya berisi penanda "kabar" tanpa isi apa pun. Isi
 * notifikasi diambil dari API EcoWin yang terautentikasi, jadi nominal dan teks tidak pernah lewat server Google.
 *
 * Aman dipanggil tanpa Firebase: bila google-services.json belum dipasang, semua fungsi di sini tidak melakukan apa pun
 * dan aplikasi memakai pengecekan berkala. Token FCM baru dibuat setelah nasabah menyalakan pemberitahuan (opt-in) dan
 * dicabut saat dimatikan/keluar akun.
 */
object Push {
    fun tersedia(c: Context): Boolean = runCatching { FirebaseApp.getApps(c.applicationContext).isNotEmpty() }.getOrDefault(false)

    private suspend fun ambilToken(): String? = suspendCancellableCoroutine { k ->
        FirebaseMessaging.getInstance().token.addOnCompleteListener { t -> if (k.isActive) k.resume(if (t.isSuccessful) t.result else null) }
    }

    /** Menyalakan pembuatan token dan mendaftarkannya ke server (hanya bila sudah opt-in dan login). */
    suspend fun daftarkan(c: Context) {
        if (!tersedia(c) || !NotifikasiHp.aktif(c)) return
        val session = SessionStore(c)
        if (!session.hasSession()) return
        runCatching {
            FirebaseMessaging.getInstance().isAutoInitEnabled = true
            val token = ambilToken() ?: return
            ApiClient(session).api.daftarPerangkat(PerangkatRequest(token))
        }
    }

    /** Melepas token di server (butuh sesi masih sah), lalu mencabutnya di perangkat. Dipanggil sebelum keluar akun/mematikan. */
    suspend fun lepas(c: Context) {
        if (!tersedia(c)) return
        runCatching {
            val session = SessionStore(c)
            if (session.hasSession()) {
                val token = ambilToken()
                if (token != null) ApiClient(session).api.lepasPerangkat(PerangkatRequest(token))
            }
        }
        matikanLokal(c)
    }

    /** Tanpa jaringan ke server EcoWin: matikan pembuatan token dan hapus token di perangkat (token lama menjadi tidak berlaku). */
    fun matikanLokal(c: Context) {
        if (!tersedia(c)) return
        runCatching {
            val fm = FirebaseMessaging.getInstance()
            fm.isAutoInitEnabled = false
            fm.deleteToken()
        }
    }
}

/** Menerima ping FCM. Isi pesan tidak dipercaya maupun ditampilkan: hanya memicu pengecekan ke server. */
class EcoWinFcmService : FirebaseMessagingService() {
    override fun onNewToken(token: String) {
        if (!NotifikasiHp.aktif(this)) return
        val session = SessionStore(this)
        if (!session.hasSession()) return
        runBlocking {
            withTimeoutOrNull(15_000) { runCatching { withContext(Dispatchers.IO) { ApiClient(session).api.daftarPerangkat(PerangkatRequest(token)) } } }
        }
    }

    override fun onMessageReceived(message: RemoteMessage) {
        if (message.data["tipe"] != "kabar") return
        // Layanan ini punya waktu singkat; selesaikan pengecekan di sini agar proses tidak dimatikan sebelum notifikasi tampil.
        runBlocking { withTimeoutOrNull(15_000) { periksaNotifikasi(applicationContext) } }
    }
}
