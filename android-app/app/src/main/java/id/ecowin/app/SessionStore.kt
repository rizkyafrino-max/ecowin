package id.ecowin.app

import android.content.Context
import android.security.keystore.KeyGenParameterSpec
import android.security.keystore.KeyProperties
import android.util.Base64
import java.security.KeyStore
import javax.crypto.Cipher
import javax.crypto.KeyGenerator
import javax.crypto.SecretKey
import javax.crypto.spec.GCMParameterSpec

/**
 * Penyimpanan token login yang aman.
 *
 * Token dienkripsi AES-256-GCM dengan kunci di Android Keystore (kunci tidak bisa
 * diekspor dari perangkat). Yang tersimpan di SharedPreferences hanya ciphertext,
 * TIDAK pernah plaintext. Backup aplikasi dimatikan di manifest.
 */
class SessionStore(context: Context) {

    private val prefs = context.applicationContext.getSharedPreferences(PREFS, Context.MODE_PRIVATE)

    @Volatile
    private var cachedAccess: String? = null

    @Volatile
    private var cachedRefresh: String? = null

    val accessToken: String?
        get() = cachedAccess ?: decrypt(prefs.getString(KEY_ACCESS, null)).also { cachedAccess = it }

    val refreshToken: String?
        get() = cachedRefresh ?: decrypt(prefs.getString(KEY_REFRESH, null)).also { cachedRefresh = it }

    fun hasSession(): Boolean = !refreshToken.isNullOrBlank()

    @Synchronized
    fun save(tokens: TokenPair) {
        cachedAccess = tokens.accessToken
        cachedRefresh = tokens.refreshToken
        prefs.edit()
            .putString(KEY_ACCESS, encrypt(tokens.accessToken))
            .putString(KEY_REFRESH, encrypt(tokens.refreshToken))
            .putString(KEY_REFRESH_EXPIRES, tokens.refreshExpiresAt)
            .apply()
    }

    @Synchronized
    fun clear() {
        cachedAccess = null
        cachedRefresh = null
        prefs.edit().clear().apply()
    }

    private fun secretKey(): SecretKey {
        val keyStore = KeyStore.getInstance(ANDROID_KEYSTORE).apply { load(null) }
        (keyStore.getEntry(KEY_ALIAS, null) as? KeyStore.SecretKeyEntry)?.let { return it.secretKey }

        val generator = KeyGenerator.getInstance(KeyProperties.KEY_ALGORITHM_AES, ANDROID_KEYSTORE)
        generator.init(
            KeyGenParameterSpec.Builder(KEY_ALIAS, KeyProperties.PURPOSE_ENCRYPT or KeyProperties.PURPOSE_DECRYPT)
                .setBlockModes(KeyProperties.BLOCK_MODE_GCM)
                .setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE)
                .setKeySize(256)
                .build()
        )
        return generator.generateKey()
    }

    private fun encrypt(plain: String): String {
        val cipher = Cipher.getInstance(TRANSFORMATION)
        cipher.init(Cipher.ENCRYPT_MODE, secretKey())
        val encrypted = cipher.doFinal(plain.toByteArray(Charsets.UTF_8))
        return Base64.encodeToString(cipher.iv, Base64.NO_WRAP) + ":" + Base64.encodeToString(encrypted, Base64.NO_WRAP)
    }

    private fun decrypt(stored: String?): String? {
        if (stored.isNullOrBlank()) return null
        return try {
            val (iv, data) = stored.split(":", limit = 2)
            val cipher = Cipher.getInstance(TRANSFORMATION)
            cipher.init(Cipher.DECRYPT_MODE, secretKey(), GCMParameterSpec(128, Base64.decode(iv, Base64.NO_WRAP)))
            String(cipher.doFinal(Base64.decode(data, Base64.NO_WRAP)), Charsets.UTF_8)
        } catch (e: Exception) {
            // Kunci hilang/rusak (mis. setelah reset keamanan perangkat) -> anggap belum login.
            null
        }
    }

    companion object {
        private const val PREFS = "ecowin_secure_session"
        private const val KEY_ACCESS = "access"
        private const val KEY_REFRESH = "refresh"
        private const val KEY_REFRESH_EXPIRES = "refresh_expires_at"
        private const val KEY_ALIAS = "ecowin_session_key"
        private const val ANDROID_KEYSTORE = "AndroidKeyStore"
        private const val TRANSFORMATION = "AES/GCM/NoPadding"
    }
}
