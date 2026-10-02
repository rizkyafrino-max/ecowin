package id.ecowin.app

data class Nasabah(val id: Long, val username: String, val nama: String, val no_hp: String, val saldo: Int? = null)
data class LoginRequest(val username: String, val pin: String)
data class LoginResponse(val nasabah: Nasabah, val token: String)
data class ChangePinRequest(val pin_lama: String, val pin_baru: String)
data class DashboardResponse(
    val jumlah_nasabah: Int,
    val total_transaksi_anorganik: Int,
    val total_berat_anorganik: Double,
    val total_saldo: Double,
    val total_organik: Double,
    val estimasi_kompos: Double,
    val jumlah_aktivitas_biopori: Int,
    val biopori_menunggu: Int,
)
data class TransactionResponse(
    val anorganik: List<AnorganicTransaction> = emptyList(),
    val organik: List<OrganicTransaction> = emptyList(),
)
data class AnorganicTransaction(val id: Long, val berat_kg: Double, val nilai_rupiah: Int, val created_at: String? = null)
data class OrganicTransaction(val id: Long, val jenis_organik: String, val berat_kg: Double, val estimasi_kompos_kg: Double, val created_at: String? = null)
data class BioporiActivity(val id: Long, val tanggal_pemasukan: String, val deskripsi: String? = null, val status: String)
data class ProfileResponse(val nasabah: Nasabah, val saldo: Int)
data class CardResponse(val qr_token: String, val username: String, val nama: String)
data class PriceCategory(val id: Long, val nama_kategori: String, val jenis_sampah: List<PriceType> = emptyList())
data class PriceType(val id: Long, val nama_jenis: String, val harga: List<PriceItem> = emptyList())
data class PriceItem(val kondisi: String, val harga_per_kg: Int, val berlaku_mulai: String)
data class Withdrawal(val id: Long, val jumlah: Int, val status: String, val created_at: String? = null)
data class WithdrawalRequest(val jumlah: Int)
data class WithdrawalResponse(val items: List<Withdrawal> = emptyList())
