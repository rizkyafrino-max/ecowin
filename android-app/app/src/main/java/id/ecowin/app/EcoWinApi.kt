package id.ecowin.app

import okhttp3.MultipartBody
import okhttp3.RequestBody
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Header
import retrofit2.http.Multipart
import retrofit2.http.PATCH
import retrofit2.http.POST
import retrofit2.http.Part

interface EcoWinApi {
    /** Satu-satunya cara login: Google ID token diverifikasi server. */
    @POST("auth/google")
    suspend fun loginGoogle(@Body body: GoogleLoginRequest): LoginResponse

    /** Daftar Nasabah baru: ID token Google + data diri. Status awal menunggu verifikasi petugas. */
    @POST("auth/register")
    suspend fun register(@Body body: RegisterRequest): LoginResponse

    @GET("bank-sampah/publik")
    suspend fun bankSampahPublik(): DataWrapper<List<BankPublikDto>>

    /** Dipanggil dengan refresh token (bukan access token). */
    @POST("auth/refresh")
    suspend fun refresh(@Header("Authorization") bearerRefresh: String): TokenPair

    @GET("auth/me")
    suspend fun me(): DataWrapper<UserDto>

    @POST("auth/logout")
    suspend fun logout()

    @PATCH("profile")
    suspend fun updateProfile(@Body body: ProfileUpdateRequest): DataWrapper<UserDto>

    @GET("dashboard/summary")
    suspend fun dashboard(): DashboardDto

    @GET("me/saldo")
    suspend fun saldo(): SaldoDto

    @GET("me/mutasi-saldo")
    suspend fun mutasi(): Paged<MutasiDto>

    @GET("me/qr")
    suspend fun qr(): QrDto

    @GET("harga")
    suspend fun harga(): DataWrapper<List<KategoriHargaDto>>

    @GET("transaksi/anorganik")
    suspend fun transaksiAnorganik(): Paged<TransaksiAnorganikDto>

    @GET("organik")
    suspend fun organik(): Paged<TransaksiOrganikDto>

    @GET("organik/biopori/lokasi")
    suspend fun lokasiBiopori(): DataWrapper<List<TitikBioporiDto>>

    @GET("organik/biopori")
    suspend fun aktivitasBiopori(): Paged<AktivitasBioporiDto>

    @Multipart
    @POST("organik/biopori")
    suspend fun laporBiopori(
        @Part("titik_biopori_id") titikId: RequestBody,
        @Part("tanggal_pemasukan") tanggal: RequestBody,
        @Part("jenis_sampah") jenisSampah: RequestBody,
        @Part("berat_kg") beratKg: RequestBody,
        @Part("catatan") catatan: RequestBody,
        @Part foto: MultipartBody.Part,
    ): DataWrapper<AktivitasBioporiDto>

    @GET("penarikan")
    suspend fun penarikan(): Paged<PenarikanDto>

    @POST("penarikan")
    suspend fun ajukanPenarikan(@Body body: PenarikanRequest): DataWrapper<PenarikanDto>
}
