package id.ecowin.app

data class Nasabah(val id: Long, val nama: String, val no_hp: String, val saldo: Double? = null)
data class LoginResponse(val nasabah: Nasabah, val token: String)
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
