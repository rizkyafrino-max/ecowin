package id.ecowin.app

import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class KunciVerifikasiTest {
    private fun nasabah(status: String?) = NasabahDto(
        id = 1, nomorNasabah = "NSB-000001", nama = "Siti", noHp = null, alamatRtRw = null, saldo = 0.0,
        status = "aktif", statusVerifikasi = status, bankSampah = null,
    )

    private fun user(role: String = "nasabah", n: NasabahDto? = nasabah("pending")) = UserDto(
        id = 1, nama = "Siti", email = "siti@gmail.com", avatar = null, jenisKelamin = null, punyaFoto = false,
        role = role, bankSampahId = null, nasabah = n,
    )

    @Test
    fun nasabahBelumDiverifikasiTerkunci() {
        assertTrue(akunTerkunci(user(n = nasabah("pending"))))
    }

    @Test
    fun nasabahTerverifikasiTidakTerkunci() {
        assertFalse(akunTerkunci(user(n = nasabah("verified"))))
    }

    @Test
    fun statusKosongDiperlakukanSebagaiBelumDiverifikasi() {
        // Lebih aman terkunci bila status tidak diketahui daripada terbuka.
        assertTrue(akunTerkunci(user(n = nasabah(null))))
        assertTrue(akunTerkunci(user(n = null)))
    }

    @Test
    fun selainNasabahTidakPernahTerkunci() {
        assertFalse(akunTerkunci(user(role = "petugas", n = null)))
        assertFalse(akunTerkunci(user(role = "admin", n = null)))
    }

    @Test
    fun halamanDataDisensorTetapHalamanAkunTerbuka() {
        val disensor = setOf(Tab.Beranda, Tab.Transaksi, Tab.Organik, Tab.Saldo, Tab.Kartu)
        assertEquals(disensor, Tab.entries.filter { tabTerkunci(it) }.toSet())
        // Profil, notifikasi, pengaturan, lapor bug, dan menu fitur harus tetap bisa dibuka.
        for (t in listOf(Tab.Profil, Tab.Notifikasi, Tab.Pengaturan, Tab.LaporBug, Tab.Fitur)) assertFalse(t.label, tabTerkunci(t))
    }
}
