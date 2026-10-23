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

    @Test
    fun autoKoma_nolDiikutiAngkaLangsungBerkoma() {
        assertEquals("0", autoKoma("0"))
        assertEquals("0,5", autoKoma("05"))
        assertEquals("0,0", autoKoma("00"))
        assertEquals("0,12", autoKoma("0123"))
        var v = ""
        for (c in "055") v = autoKoma(v + c)
        assertEquals("0,55", v)
    }

    @Test
    fun autoKoma_angkaBiasaDanSimbol() {
        assertEquals("10", autoKoma("10"))
        assertEquals("250", autoKoma("250"))
        assertEquals("1,5", autoKoma("1.5"))
        assertEquals("1,55", autoKoma("1,5,5"))
        assertEquals("1,2", autoKoma("a1b,2c"))
        assertEquals("0,5", autoKoma("00,5"))
        assertEquals("0,", autoKoma("0,"))
    }

    @Test
    fun keDoubleDanSoloAngka() {
        assertEquals(0.5, keDouble("0,5")!!, 0.0)
        assertNull(keDouble("abc"))
        assertEquals("5000", soloAngka("05000"))
        assertEquals("0", soloAngka("0"))
        assertEquals("10005", soloAngka("1.000,5"))
    }

    @Test
    fun avatarServer_hanyaJalurDikenalDanAsalDariApiSendiri() {
        val kode = "a".repeat(32)
        val dasar = "http://127.0.0.1:8000/api/"
        assertEquals("http://127.0.0.1:8000/foto/$kode", safeAvatarUrl("http://127.0.0.1:8000/foto/$kode", dasar))
        assertEquals("http://127.0.0.1:8000/images/avatar-laki.jpg", safeAvatarUrl("http://localhost:8000/images/avatar-laki.jpg", dasar))
        assertEquals("https://api.ecowin.id/images/avatar-perempuan.jpg", safeAvatarUrl("https://api.ecowin.id/images/avatar-perempuan.jpg", "https://api.ecowin.id/api/"))
        // host dari server tidak pernah dipakai: asal selalu dari alamat API aplikasi
        assertEquals("https://api.ecowin.id/foto/$kode", safeAvatarUrl("https://evil.example.com/foto/$kode", "https://api.ecowin.id/api/"))
        // jalur tidak dikenal atau bermuatan tambahan ditolak
        assertNull(safeAvatarUrl("https://api.ecowin.id/foto/pendek", "https://api.ecowin.id/api/"))
        assertNull(safeAvatarUrl("https://api.ecowin.id/foto/" + "b".repeat(33), "https://api.ecowin.id/api/"))
        assertNull(safeAvatarUrl("https://api.ecowin.id/images/avatar-x.jpg", "https://api.ecowin.id/api/"))
        assertNull(safeAvatarUrl("https://api.ecowin.id/foto/$kode?x=1", "https://api.ecowin.id/api/"))
        assertNull(safeAvatarUrl("https://api.ecowin.id/foto/$kode#a", "https://api.ecowin.id/api/"))
        assertNull(safeAvatarUrl("https://u:p@api.ecowin.id/foto/$kode", "https://api.ecowin.id/api/"))
        assertNull(safeAvatarUrl("https://api.ecowin.id/foto/../etc", "https://api.ecowin.id/api/"))
        assertNull(safeAvatarUrl("https://api.ecowin.id/foto/$kode", "ftp://api.ecowin.id/api/"))
    }
}
