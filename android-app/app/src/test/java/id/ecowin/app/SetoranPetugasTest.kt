package id.ecowin.app

import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class SetoranPetugasTest {
    private fun nasabah(status: String, verif: String?) = NasabahDto(
        id = 1, nomorNasabah = "NSB-1", nama = "Siti", noHp = null, alamatRtRw = null, saldo = 0.0,
        status = status, statusVerifikasi = verif, bankSampah = null,
    )

    @Test
    fun tokenQrHanyaHurufAngkaPanjangWajar() {
        assertTrue(tokenQrValid("a".repeat(40)))
        assertFalse(tokenQrValid("pendek"))
        assertFalse(tokenQrValid("https://situs-jahat.example/" + "a".repeat(40)))
        assertFalse(tokenQrValid(null))
    }

    @Test
    fun hanyaNasabahAktifTerverifikasiBolehDisetori() {
        assertTrue(bolehDisetori(nasabah("aktif", "verified")))
        assertFalse(bolehDisetori(nasabah("aktif", "pending")))
        assertFalse(bolehDisetori(nasabah("nonaktif", "verified")))
    }

    @Test
    fun beratHarusPositifDanDalamBatas() {
        assertTrue(beratSetoranValid("2,5"))
        assertTrue(beratSetoranValid("10000"))
        assertFalse(beratSetoranValid(""))
        assertFalse(beratSetoranValid("0"))
        assertFalse(beratSetoranValid("10001"))
    }
}
