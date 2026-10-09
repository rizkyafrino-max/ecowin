package id.ecowin.app

import android.app.Activity
import android.content.Intent
import android.os.Bundle

/**
 * Alat pengembangan, hanya ada di build debug: menyimpan token dari `adb shell am start ... --es access .. --es refresh ..`
 * ke SessionStore lalu membuka aplikasi. Token tetap harus valid di server; tidak ada bypass autentikasi.
 */
class DebugLoginActivity : Activity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        val access = intent.getStringExtra("access")
        val refresh = intent.getStringExtra("refresh")
        if (!access.isNullOrBlank() && !refresh.isNullOrBlank()) {
            SessionStore(this).save(TokenPair(accessToken = access, refreshToken = refresh))
        }
        startActivity(Intent(this, MainActivity::class.java).addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP))
        finish()
    }
}
