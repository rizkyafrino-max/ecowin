package id.ecowin.app

import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class PenarikanPetugasTest {
    private fun p(status: String) = PenarikanDto(id = 1, jumlah = 10000, status = status, catatan = null, diprosesAt = null, createdAt = null)

    @Test
    fun hanyaYangPendingBisaDiputuskan() {
        assertTrue(penarikanMenunggu(p("pending")))
        assertFalse(penarikanMenunggu(p("approved")))
        assertFalse(penarikanMenunggu(p("completed")))
    }

    @Test
    fun hanyaYangDisetujuiPerluDiserahkan() {
        assertTrue(penarikanPerluDiserahkan(p("approved")))
        assertFalse(penarikanPerluDiserahkan(p("pending")))
        assertFalse(penarikanPerluDiserahkan(p("rejected")))
    }
}
