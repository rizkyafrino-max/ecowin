package id.ecowin.app

/*
 * Model respons API Laravel. Gson memetakan snake_case -> camelCase
 * (FieldNamingPolicy.LOWER_CASE_WITH_UNDERSCORES di ApiClient).
 */

data class GoogleLoginRequest(val idToken: String, val deviceName: String)

data class StatistikDto(val labels: List<String> = emptyList(), val berat: List<Double> = emptyList(), val nilai: List<Long> = emptyList())

data class RegisterRequest(
    val idToken: String,
    val deviceName: String,
    val nama: String,
    val noHp: String,
    val alamatRtRw: String,
    val bankSampahId: Long,
    val setuju: Boolean,
)

data class BankPublikDto(val id: Long, val nama: String?, val rt: String?, val rw: String?)

data class TokenPair(
    val tokenType: String = "Bearer",
    val accessToken: String,
    val accessExpiresAt: String? = null,
    val refreshToken: String,
    val refreshExpiresAt: String? = null,
)

data class LoginResponse(
    val tokenType: String?,
    val accessToken: String,
    val accessExpiresAt: String?,
    val refreshToken: String,
    val refreshExpiresAt: String?,
    val user: UserDto,
) {
    fun tokens() = TokenPair(tokenType ?: "Bearer", accessToken, accessExpiresAt, refreshToken, refreshExpiresAt)
}

data class DataWrapper<T>(val data: T)

data class Paged<T>(val data: List<T>)

data class UserDto(
    val id: Long,
    val nama: String?,
    val email: String?,
    val avatar: String?,
    val role: String,
    val bankSampahId: Long?,
    val nasabah: NasabahDto?,
)

data class BankSampahDto(val id: Long, val nama: String?, val rt: String?, val rw: String?)

data class NasabahDto(
    val id: Long,
    val nomorNasabah: String?,
    val nama: String?,
    val noHp: String?,
    val alamatRtRw: String?,
    val saldo: Double = 0.0,
    val status: String?,
    val statusVerifikasi: String? = null,
    val bankSampah: BankSampahDto?,
)

data class SaldoDto(
    val saldo: Double,
    val saldoDitahan: Double,
    val saldoTersedia: Double,
    val totalPemasukan: Double,
    val totalPenarikan: Double,
)

data class MutasiDto(
    val id: Long,
    val tipe: String,
    val jumlah: Double,
    val saldoSebelum: Double,
    val saldoSesudah: Double,
    val keterangan: String?,
    val createdAt: String?,
)

data class QrDto(val nomorNasabah: String?, val nama: String?, val qrValue: String, val qrPngBase64: String)

data class TransaksiAnorganikDto(
    val id: Long,
    val jenisSampah: String?,
    val beratKg: Double,
    val hargaPerKg: Double,
    val nilaiRupiah: Long,
    val createdAt: String?,
)

data class TransaksiOrganikDto(
    val id: Long,
    val tanggal: String?,
    val jenisOrganik: String?,
    val lokasi: String?,
    val metodePengolahan: String?,
    val beratKg: Double,
    val estimasiKomposKg: Double,
    val statusPengolahan: String?,
)

data class TitikBioporiDto(
    val id: Long,
    val namaLokasi: String?,
    val deskripsiLokasi: String?,
    val latitude: Double? = null,
    val longitude: Double? = null,
    val bioporiprint: Boolean = true,
    val status: String?,
    val terakhirDiisiAt: String?,
    val estimasiPanenAt: String?,
    val statusPanen: String?,
)

data class AktivitasBioporiDto(
    val id: Long,
    val lokasi: String?,
    val tanggalPemasukan: String?,
    val jenisSampah: String?,
    val beratKg: Double,
    val catatan: String?,
    val status: String,
    val catatanPetugas: String?,
    val waktuDiperiksa: String?,
    val fotoUrl: String?,
)

data class PenarikanDto(
    val id: Long,
    val jumlah: Long,
    val status: String,
    val catatan: String?,
    val diprosesAt: String?,
    val createdAt: String?,
)

data class PenarikanRequest(val jumlah: Long, val catatan: String?)

data class ProfileUpdateRequest(val nama: String?, val noHp: String?, val alamatRtRw: String?)

data class AnorganikSummary(val totalBeratKg: Double = 0.0, val jumlahTransaksi: Int = 0, val totalNilai: Long = 0)

data class OrganikSummary(
    val totalBeratKg: Double = 0.0,
    val jumlahAktivitas: Int = 0,
    val estimasiKomposKg: Double = 0.0,
    val jumlahAktivitasBiopori: Int = 0,
    val bioporiPending: Int = 0,
    val bioporiTerverifikasi: Int = 0,
)

data class DashboardDto(
    val nama: String?,
    val saldo: Double?,
    val anorganik: AnorganikSummary?,
    val organik: OrganikSummary?,
    val transaksiTerakhir: List<TransaksiAnorganikDto>?,
    val bioporiTerakhir: List<AktivitasBioporiDto>?,
)

data class HargaDto(val minimalBerat: Double, val maksimalBerat: Double?, val hargaPerKg: Long, val kondisi: String?)

data class JenisHargaDto(val id: Long, val namaJenis: String, val satuan: String?, val harga: List<HargaDto>)

data class KategoriHargaDto(val id: Long, val namaKategori: String, val jenisSampah: List<JenisHargaDto>)

data class ApiError(val message: String?, val errors: Map<String, List<String>>?)
