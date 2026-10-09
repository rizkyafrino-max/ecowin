package id.ecowin.app

import com.google.gson.FieldNamingPolicy
import com.google.gson.Gson
import com.google.gson.GsonBuilder
import kotlinx.coroutines.flow.MutableSharedFlow
import kotlinx.coroutines.flow.SharedFlow
import kotlinx.coroutines.runBlocking
import okhttp3.Authenticator
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.Response
import okhttp3.Route
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.HttpException
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

/**
 * Klien HTTP:
 * - menambahkan "Authorization: Bearer <access token>" otomatis,
 * - bila access token kedaluwarsa (401) -> refresh otomatis sekali lalu ulangi request,
 * - bila refresh gagal/dicabut -> sesi dihapus dan aplikasi kembali ke layar login.
 */
class ApiClient(private val session: SessionStore) {

    val gson: Gson = GsonBuilder().setFieldNamingPolicy(FieldNamingPolicy.LOWER_CASE_WITH_UNDERSCORES).create()

    private val _sessionExpired = MutableSharedFlow<Unit>(extraBufferCapacity = 1)
    val sessionExpired: SharedFlow<Unit> = _sessionExpired

    private val baseUrl = BuildConfig.API_BASE_URL.let { if (it.endsWith("/")) it else "$it/" }

    private val authInterceptor = Interceptor { chain ->
        val original = chain.request()
        val builder = original.newBuilder().header("Accept", "application/json")
        val token = session.accessToken
        if (original.header("Authorization") == null && token != null) {
            builder.header("Authorization", "Bearer $token")
        }
        chain.proceed(builder.build())
    }

    private val plainClient: OkHttpClient = OkHttpClient.Builder()
        .connectTimeout(15, TimeUnit.SECONDS)
        .readTimeout(30, TimeUnit.SECONDS)
        .build()

    private val refreshApi: EcoWinApi = retrofit(plainClient.newBuilder().addInterceptor { chain ->
        chain.proceed(chain.request().newBuilder().header("Accept", "application/json").build())
    }.build())

    private val tokenAuthenticator = object : Authenticator {
        override fun authenticate(route: Route?, response: Response): Request? {
            val path = response.request.url.encodedPath
            if (path.endsWith("/auth/google") || path.endsWith("/auth/refresh") || responseCount(response) >= 2) {
                return null
            }

            synchronized(this) {
                val currentAccess = session.accessToken
                val usedAccess = response.request.header("Authorization")?.removePrefix("Bearer ")

                // Token sudah diperbarui oleh request lain yang paralel.
                if (currentAccess != null && currentAccess != usedAccess) {
                    return response.request.newBuilder().header("Authorization", "Bearer $currentAccess").build()
                }

                val refresh = session.refreshToken ?: return expire()

                return try {
                    val pair = runBlocking { refreshApi.refresh("Bearer $refresh") }
                    session.save(pair)
                    response.request.newBuilder().header("Authorization", "Bearer ${pair.accessToken}").build()
                } catch (e: HttpException) {
                    expire()
                } catch (e: Exception) {
                    null // jaringan bermasalah: jangan hapus sesi
                }
            }
        }

        private fun expire(): Request? {
            session.clear()
            _sessionExpired.tryEmit(Unit)
            return null
        }

        private fun responseCount(response: Response): Int {
            var count = 1
            var prior = response.priorResponse
            while (prior != null) {
                count++
                prior = prior.priorResponse
            }
            return count
        }
    }

    val httpClient: OkHttpClient = plainClient.newBuilder()
        .addInterceptor(authInterceptor)
        .authenticator(tokenAuthenticator)
        .apply {
            if (BuildConfig.DEBUG) {
                // BASIC: tidak mencetak header Authorization / isi token ke log.
                addInterceptor(HttpLoggingInterceptor().apply { level = HttpLoggingInterceptor.Level.BASIC })
            }
        }
        .build()

    val api: EcoWinApi = retrofit(httpClient)

    private fun retrofit(client: OkHttpClient): EcoWinApi = Retrofit.Builder()
        .baseUrl(baseUrl)
        .client(client)
        .addConverterFactory(GsonConverterFactory.create(gson))
        .build()
        .create(EcoWinApi::class.java)

    /** Ubah exception menjadi pesan yang ramah untuk pengguna. */
    fun errorMessage(e: Throwable): String = when (e) {
        is HttpException -> {
            val body = e.response()?.errorBody()?.string()
            val parsed = runCatching { gson.fromJson(body, ApiError::class.java) }.getOrNull()
            parsed?.errors?.values?.flatten()?.firstOrNull()
                ?: parsed?.message
                ?: when (e.code()) {
                    401 -> "Sesi berakhir. Silakan masuk lagi."
                    403 -> "Anda tidak memiliki akses."
                    404 -> "Data tidak ditemukan."
                    429 -> "Terlalu banyak percobaan. Coba lagi sebentar."
                    else -> "Terjadi kesalahan server (${e.code()})."
                }
        }
        is java.io.IOException -> "Tidak dapat terhubung ke server. Periksa koneksi internet."
        else -> e.message ?: "Terjadi kesalahan."
    }
}
