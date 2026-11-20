package id.ecowin.app

import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class PetugasPeranTest {
    @Test
    fun nasabahDanPetugasBolehMasuk() {
        assertTrue(peranBolehMasuk("nasabah"))
        assertTrue(peranBolehMasuk("petugas"))
    }

    @Test
    fun adminDanLainnyaDitolak() {
        assertFalse(peranBolehMasuk("admin"))
        assertFalse(peranBolehMasuk(null))
        assertFalse(peranBolehMasuk(""))
    }
}
