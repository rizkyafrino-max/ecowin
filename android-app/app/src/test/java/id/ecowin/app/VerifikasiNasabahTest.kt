package id.ecowin.app

import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class VerifikasiNasabahTest {
    @Test
    fun alasanTolakHarusCukupPanjang() {
        assertFalse(alasanTolakValid(""))
        assertFalse(alasanTolakValid("   ab  "))
        assertTrue(alasanTolakValid("Data tidak sesuai"))
    }
}
