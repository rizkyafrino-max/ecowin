package id.ecowin.app

import retrofit2.http.GET
import retrofit2.http.Header
import retrofit2.http.POST
import retrofit2.http.Body
import retrofit2.http.Multipart
import retrofit2.http.Part
import retrofit2.http.Query
import okhttp3.MultipartBody
import okhttp3.RequestBody

data class LoginRequest(val no_hp: String, val pin: String)

interface EcoWinApi {
    @POST("api/auth/nasabah-login")
    suspend fun login(@Body request: LoginRequest): LoginResponse

    @GET("api/dashboard/ringkasan")
    suspend fun dashboard(@Header("Authorization") token: String): DashboardResponse

    @GET("api/transaksi")
    suspend fun transactions(@Header("Authorization") token: String, @Query("tipe") type: String = "semua"): TransactionResponse

    @GET("api/organik/biopori")
    suspend fun biopori(@Header("Authorization") token: String): List<BioporiActivity>

    @Multipart
    @POST("api/organik/biopori")
    suspend fun submitBiopori(
        @Header("Authorization") token: String,
        @Part("tanggal_pemasukan") date: RequestBody,
        @Part("deskripsi") description: RequestBody,
        @Part photo: MultipartBody.Part,
    ): BioporiActivity
}
