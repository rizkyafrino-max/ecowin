package id.ecowin.app

import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory

object ApiClient {
    // Ganti dengan alamat LAN komputer yang menjalankan Laravel saat memakai ponsel fisik.
    private const val BASE_URL = "http://10.0.2.2:8000/"

    val api: EcoWinApi by lazy {
        Retrofit.Builder()
            .baseUrl(BASE_URL)
            .addConverterFactory(GsonConverterFactory.create())
            .build()
            .create(EcoWinApi::class.java)
    }
}
