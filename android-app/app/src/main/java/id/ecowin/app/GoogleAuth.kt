package id.ecowin.app

import android.app.Activity
import androidx.credentials.ClearCredentialStateRequest
import androidx.credentials.CredentialManager
import androidx.credentials.CustomCredential
import androidx.credentials.GetCredentialRequest
import androidx.credentials.exceptions.GetCredentialCancellationException
import androidx.credentials.exceptions.GetCredentialException
import androidx.credentials.exceptions.NoCredentialException
import com.google.android.libraries.identity.googleid.GetSignInWithGoogleOption
import com.google.android.libraries.identity.googleid.GoogleIdTokenCredential
import java.security.SecureRandom
import android.util.Base64

/**
 * Login dengan akun Google lewat Credential Manager. Aplikasi hanya meneruskan
 * ID token ke server; email/role TIDAK pernah dikirim atau dipercaya dari sisi aplikasi.
 */
class GoogleAuth(private val activity: Activity) {

    private val credentialManager = CredentialManager.create(activity)

    sealed interface Result {
        data class Success(val idToken: String) : Result
        data object Cancelled : Result
        data class Failure(val message: String) : Result
    }

    suspend fun signIn(): Result {
        val clientId = BuildConfig.GOOGLE_WEB_CLIENT_ID
        if (clientId.isBlank()) {
            return Result.Failure("Google Client ID belum diatur (ECOWIN_GOOGLE_WEB_CLIENT_ID di gradle.properties).")
        }

        val option = GetSignInWithGoogleOption.Builder(clientId)
            .setNonce(randomNonce())
            .build()

        return try {
            val response = credentialManager.getCredential(activity, GetCredentialRequest.Builder().addCredentialOption(option).build())
            val credential = response.credential

            if (credential is CustomCredential && credential.type == GoogleIdTokenCredential.TYPE_GOOGLE_ID_TOKEN_CREDENTIAL) {
                Result.Success(GoogleIdTokenCredential.createFrom(credential.data).idToken)
            } else {
                Result.Failure("Jenis kredensial tidak dikenali.")
            }
        } catch (e: GetCredentialCancellationException) {
            Result.Cancelled
        } catch (e: NoCredentialException) {
            Result.Failure("Tidak ada akun Google di perangkat ini.")
        } catch (e: GetCredentialException) {
            Result.Failure(e.message ?: "Login Google gagal.")
        }
    }

    /** Dipanggil saat logout agar pemilihan akun Google tidak otomatis lagi. */
    suspend fun signOut() {
        runCatching { credentialManager.clearCredentialState(ClearCredentialStateRequest()) }
    }

    private fun randomNonce(): String {
        val bytes = ByteArray(32).also { SecureRandom().nextBytes(it) }
        return Base64.encodeToString(bytes, Base64.URL_SAFE or Base64.NO_WRAP or Base64.NO_PADDING)
    }
}
