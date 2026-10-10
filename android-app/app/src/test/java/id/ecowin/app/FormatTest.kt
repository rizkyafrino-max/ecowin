package id.ecowin.app

import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test

class FormatTest {
    @Test
    fun rupiah_memakaiPemisahTitik() {
        assertEquals("Rp 1.250.000", rupiah(1_250_000))
        assertEquals("Rp 0", rupiah(null))
    }

    @Test
    fun kg_duaDesimalMaksimal() {
        assertEquals("12,5 kg", kg(12.5))
        assertEquals("0 kg", kg(null))
    }

    @Test
    fun tanggal_formatIndonesiaDanTahanKesalahan() {
        assertEquals("-", tanggal(null))
        assertEquals("-", tanggal(""))
        assertEquals("8 Okt 2026", tanggal("2026-10-08T12:00:00+00:00", withTime = false))
        assertEquals("2026-10-08", tanggal("2026-10-08xx"))
    }

    @Test
    fun inisial_hurufPertamaKapital() {
        assertEquals("A", inisial("agus widiyono"))
        assertEquals("R", inisial("  Rizky"))
        assertEquals("?", inisial(null))
        assertEquals("?", inisial(""))
    }

    @Test
    fun statusLabel_memetakanStatusServer() {
        assertEquals("Menunggu", statusLabel("pending"))
        assertEquals("Disetujui", statusLabel("approved"))
        assertEquals("Ditolak", statusLabel("rejected"))
        assertEquals("Selesai", statusLabel("completed"))
        assertEquals("Siap panen", statusLabel("siap_panen"))
        assertEquals("-", statusLabel(null))
    }

    @Test
    fun avatar_hanyaHttpsDanHostGoogle() {
        assertEquals("https://lh3.googleusercontent.com/a/abc=s96-c", safeAvatarUrl("https://lh3.googleusercontent.com/a/abc=s96-c"))
        assertNull(safeAvatarUrl("http://lh3.googleusercontent.com/a/abc"))
        assertNull(safeAvatarUrl("https://evil.example.com/a.png"))
        assertNull(safeAvatarUrl("https://googleusercontent.com.evil.com/a.png"))
        assertNull(safeAvatarUrl("https://user:pw@lh3.googleusercontent.com/a.png"))
        assertNull(safeAvatarUrl("file:///sdcard/a.png"))
        assertNull(safeAvatarUrl(null))
        assertNull(safeAvatarUrl("https://lh3.googleusercontent.com/" + "a".repeat(600)))
    }
}
