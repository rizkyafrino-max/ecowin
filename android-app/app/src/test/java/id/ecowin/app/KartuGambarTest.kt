package id.ecowin.app

import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test

class KartuGambarTest {
    @Test
    fun namaBerkas_hanyaMemuatHurufAngkaDanStrip() {
        assertEquals("Kartu-EcoWin-NSB-000001.png", KartuGambar.namaBerkas("NSB-000001"))
        assertEquals("Kartu-EcoWin-etcpasswd.png", KartuGambar.namaBerkas("../../etc/passwd<>"))
        assertEquals("Kartu-EcoWin-nasabah.png", KartuGambar.namaBerkas(null))
        assertEquals("Kartu-EcoWin-nasabah.png", KartuGambar.namaBerkas("///"))
    }

    // Setiap karakter dianggap selebar 10 satuan.
    private val ukur: (String) -> Float = { it.length * 10f }

    @Test
    fun bungkus_namaPendekSatuBaris() {
        assertEquals(listOf("Siti Rahma"), KartuGambar.bungkus("Siti Rahma", 500f, 2, ukur))
    }

    @Test
    fun bungkus_namaPanjangMenjadiDuaBarisLalu_elipsis() {
        val dua = KartuGambar.bungkus("Muhammad Abdurrahman Al Fatih", 200f, 2, ukur)
        assertEquals(2, dua.size)
        assertTrue(dua.all { ukur(it) <= 200f })

        val panjang = KartuGambar.bungkus("Muhammad Abdurrahman Al Fatih bin Sulaiman Hakim Nugroho Pratama", 200f, 2, ukur)
        assertEquals(2, panjang.size)
        assertTrue(panjang.last().endsWith("…"))
        assertTrue(ukur(panjang.last()) <= 200f)
    }

    @Test
    fun bungkus_kataTunggalSangatPanjangTidakMenggantungDanTetapDipotong() {
        val hasil = KartuGambar.bungkus("A".repeat(80), 200f, 1, ukur)
        assertEquals(1, hasil.size)
        assertTrue(hasil[0].endsWith("…"))
        assertTrue(ukur(hasil[0]) <= 200f)
    }

    @Test
    fun bungkus_teksKosongTidakMenghasilkanBaris() {
        assertEquals(emptyList<String>(), KartuGambar.bungkus("   ", 200f, 2, ukur))
    }

    @Test
    fun ringkasBank_memuatNamaDanRtRw() {
        assertEquals("EcoWin RT 01 · RT 01/RW 05", ringkasBank(BankSampahDto(1, "EcoWin RT 01", "01", "05")))
        assertEquals("EcoWin RT 01 · RT 01", ringkasBank(BankSampahDto(1, "EcoWin RT 01", "01", null)))
        assertEquals("EcoWin RT 01", ringkasBank(BankSampahDto(1, "EcoWin RT 01", null, null)))
        assertEquals("", ringkasBank(null))
    }
}
