package id.ecowin.app

import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test

class NotifikasiHpTest {
    private fun n(id: Long, dibaca: Boolean = false) = NotifikasiDto(id = id, tipe = "setoran_anorganik", judul = "J$id", isi = "I$id", dibaca = dibaca)

    @Test fun `tidak ada yang baru jika semua sudah lewat acuan atau sudah dibaca`() {
        assertNull(ringkasBaru(listOf(n(5), n(4, dibaca = true)), idTerakhir = 5))
        assertNull(ringkasBaru(emptyList(), idTerakhir = 0))
    }

    @Test fun `hanya yang belum dibaca dan lebih baru dari acuan yang dihitung`() {
        val r = ringkasBaru(listOf(n(9), n(8, dibaca = true), n(7), n(3)), idTerakhir = 4)!!
        assertEquals(2, r.jumlah)
        assertEquals(9L, r.idTertinggi)
        assertEquals("J9", r.terbaru.judul)
    }

    @Test fun `urutan daftar tidak mempengaruhi notifikasi terbaru`() {
        val r = ringkasBaru(listOf(n(1), n(12), n(6)), idTerakhir = 0)!!
        assertEquals(12L, r.terbaru.id)
        assertEquals(3, r.jumlah)
    }
}
