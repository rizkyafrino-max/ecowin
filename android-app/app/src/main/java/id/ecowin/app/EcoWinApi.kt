package id.ecowin.app

import okhttp3.MultipartBody
import okhttp3.RequestBody
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Header
import retrofit2.http.Multipart
import retrofit2.http.POST
import retrofit2.http.Part
import retrofit2.http.Query

interface EcoWinApi {
    @POST("api/auth/nasabah-login")
    suspend fun login(@Body request: LoginRequest): LoginResponse

    @POST("api/auth/logout")
    suspend fun logout(@Header("Authorization") token: String)

    @retrofit2.http.PATCH("api/auth/nasabah-pin")
    suspend fun changePin(@Header("Authorization") token: String, @Body request: ChangePinRequest)

    @GET("api/dashboard/ringkasan")
    suspend fun dashboard(@Header("Authorization") token: String): DashboardResponse

    @GET("api/nasabah/me")
    suspend fun profile(@Header("Authorization") token: String): ProfileResponse

    @GET("api/nasabah/kartu")
    suspend fun card(@Header("Authorization") token: String): CardResponse

    @GET("api/harga")
    suspend fun prices(@Header("Authorization") token: String): List<PriceCategory>

    @GET("api/transaksi")
    suspend fun transactions(@Header("Authorization") token: String, @Query("tipe") type: String = "semua"): TransactionResponse

    @POST("api/penarikan")
    suspend fun requestWithdrawal(@Header("Authorization") token: String, @Body request: WithdrawalRequest): Withdrawal

    @GET("api/penarikan")
    suspend fun withdrawals(@Header("Authorization") token: String): List<Withdrawal>

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
