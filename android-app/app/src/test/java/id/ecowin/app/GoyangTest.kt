package id.ecowin.app

import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class GoyangTest {
    @Test
    fun tigaGuncanganKuatDalamSatuJendelaMemicu() {
        val h = PenghitungGoyang()
        assertFalse(h.masuk(3f, 1_000))
        assertFalse(h.masuk(3f, 1_300))
        assertTrue(h.masuk(3f, 1_600))
    }

    @Test
    fun gerakanBiasaDanHentakanTunggalTidakMemicu() {
        val h = PenghitungGoyang()
        // HP diam/berjalan pelan (<= 1,5 g) tidak pernah terhitung
        for (t in 0..5_000 step 20) assertFalse(h.masuk(1.5f, t.toLong()))
        // satu hentakan panjang (puncak berdempetan < 120 ms) dihitung satu kali saja
        val h2 = PenghitungGoyang()
        for (t in 0..100 step 20) assertFalse(h2.masuk(4f, 2_000L + t))
    }

    @Test
    fun puncakYangTerlaluJauhTidakDigabung() {
        val h = PenghitungGoyang()
        assertFalse(h.masuk(3f, 1_000))
        assertFalse(h.masuk(3f, 2_500)) // puncak pertama sudah di luar jendela 1,2 dtk
        assertFalse(h.masuk(3f, 4_000))
    }

    @Test
    fun adaMasaTenangSetelahTerpicu() {
        val h = PenghitungGoyang()
        h.masuk(3f, 1_000); h.masuk(3f, 1_300)
        assertTrue(h.masuk(3f, 1_600))
        // guncangan lagi dalam 3 dtk diabaikan
        assertFalse(h.masuk(3f, 1_800)); assertFalse(h.masuk(3f, 2_000)); assertFalse(h.masuk(3f, 2_200))
        // setelah masa tenang bisa memicu lagi
        assertFalse(h.masuk(3f, 5_000)); assertFalse(h.masuk(3f, 5_300))
        assertTrue(h.masuk(3f, 5_600))
    }

    @Test
    fun susunLaporanMemotongTeksDanSelaluMuatDiBatasServer() {
        val info = "Layar: Pengaturan\nEcoWin 2.0 • Android 14 (API 34) • Samsung SM-A546E"
        val hasil = susunLaporan("  " + "x".repeat(5_000) + "  ", info)
        assertTrue(hasil.length <= 2_000)
        assertTrue(hasil.endsWith(info))
        assertEquals("Halo\n\n— Info otomatis —\n$info", susunLaporan("  Halo ", info))
    }
}
