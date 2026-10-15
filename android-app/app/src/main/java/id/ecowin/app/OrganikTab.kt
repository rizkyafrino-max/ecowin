package id.ecowin.app

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.clickable
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

/**
 * Organik: satu fitur untuk semua aktivitas sampah organik. Menggabungkan setoran organik yang dicatat
 * petugas dan aktivitas BioporiPrint yang dikirim Nasabah (foto bukti, menunggu verifikasi petugas).
 * Organik tidak menghasilkan saldo rupiah.
 */
@Composable
fun OrganikTab(client: ApiClient, refreshKey: Int) {
    var formOpen by rememberSaveable { mutableStateOf(false) }
    var titikFormOpen by rememberSaveable { mutableStateOf(false) }
    var titikTerkirim by remember { mutableStateOf(false) }
    var localRefresh by remember { mutableStateOf(0) }
    val setoran = rememberLoadable(client, refreshKey, localRefresh) { client.api.organik().data }
    val aktivitas = rememberLoadable(client, refreshKey, localRefresh) { client.api.aktivitasBiopori().data }
    val lokasi = rememberLoadable(client, refreshKey, localRefresh) { client.api.lokasiBiopori().data }

    TabList {
        item {
            if (formOpen) {
                TambahAktivitasForm(client, lokasi, onDone = { formOpen = false; localRefresh++ }, onCancel = { formOpen = false })
            } else {
                PrimaryButton("+ Tambah Aktivitas") { formOpen = true }
            }
        }

        item {
            val totalSetoran = setoran.data.orEmpty().sumOf { it.beratKg }
            val totalKompos = setoran.data.orEmpty().sumOf { it.estimasiKomposKg }
            val totalAktivitas = aktivitas.data.orEmpty().filter { it.status == "approved" || it.status == "completed" }.sumOf { it.beratKg }
            StatTile("Total Organik", kg(totalSetoran + totalAktivitas), Modifier.fillMaxWidth(), icon = Lucide.Recycle, hint = "Estimasi kompos " + kg(totalKompos))
        }

        item { OrganikFlow() }

        item {
            LoadableContent(setoran) { list ->
                LoadableContent(aktivitas) { akt ->
                    Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                        SectionTitle("Aktivitas Terbaru")
                        if (list.isEmpty() && akt.isEmpty()) ListCard { EmptyText("Belum ada aktivitas organik. Tambahkan aktivitas pertama Anda dengan foto bukti.") }
                        // Gabungan dua sumber, terbaru di atas.
                        val gabungan = list.map { Triple(it.tanggal.orEmpty(), true, it) } +
                            akt.map { Triple(it.tanggalPemasukan.orEmpty(), false, it) }
                        if (list.isNotEmpty() || akt.isNotEmpty()) ListCard {
                            gabungan.sortedByDescending { it.first }.forEachIndexed { i, (_, setoranCatatan, data) ->
                                if (i > 0) RowDivider()
                                if (setoranCatatan) {
                                    val o = data as TransaksiOrganikDto
                                    ActivityRow(Lucide.Recycle, o.jenisOrganik ?: "Setoran organik", tanggal(o.tanggal, false) + " · " + (o.lokasi ?: "Tanpa lokasi"), kg(o.beratKg), o.statusPengolahan)
                                } else {
                                    val a = data as AktivitasBioporiDto
                                    ActivityRow(Lucide.Leaf, a.jenisSampah ?: "Aktivitas organik", (a.lokasi ?: "Lokasi") + " · " + tanggal(a.tanggalPemasukan, false), kg(a.beratKg), a.status)
                                }
                            }
                        }
                    }
                }
            }
        }

        if (titikFormOpen) {
            item {
                AjukanTitikForm(client, onDone = { titikFormOpen = false; titikTerkirim = true; localRefresh++ }, onCancel = { titikFormOpen = false })
            }
        }
        if (titikTerkirim) {
            item {
                Text("Titik diajukan. Menunggu persetujuan petugas.", color = Emerald, fontWeight = FontWeight.SemiBold, fontSize = 14.sp,
                    modifier = Modifier.fillMaxWidth().background(EmeraldSoft, androidx.compose.foundation.shape.RoundedCornerShape(14.dp)).padding(14.dp))
            }
        }

        item {
            LoadableContent(lokasi) { list ->
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    val resmi = list.filter { (it.verifikasi ?: "approved") == "approved" }
                    val pengajuan = list.filter { it.diajukanSaya && it.verifikasi != null && it.verifikasi != "approved" }
                    if (resmi.isNotEmpty()) PetaBioporiCard(resmi)
                    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                        SectionTitle("Lokasi BioporiPrint")
                        Text(
                            "+ Ajukan titik", color = Emerald, fontWeight = FontWeight.SemiBold, fontSize = 13.sp,
                            modifier = Modifier
                                .background(EmeraldSoft, androidx.compose.foundation.shape.RoundedCornerShape(50))
                                .clickable { titikTerkirim = false; titikFormOpen = true }
                                .padding(horizontal = 14.dp, vertical = 8.dp),
                        )
                    }
                    if (resmi.isEmpty() && pengajuan.isEmpty()) EmptyText("Belum ada lokasi di Bank Sampah Anda. Ajukan titik pertama agar bisa melapor aktivitas.")
                    if (pengajuan.isNotEmpty()) {
                        Text("Pengajuan Anda", color = Slate500, fontWeight = FontWeight.SemiBold, fontSize = 13.sp)
                        pengajuan.forEach { t ->
                            EcoCard {
                                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                                    Row(Modifier.weight(1f), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                        androidx.compose.material3.Icon(Lucide.MapPin, contentDescription = null, tint = Emerald, modifier = Modifier.size(18.dp))
                                        Text(t.namaLokasi ?: "Lokasi", fontWeight = FontWeight.SemiBold)
                                    }
                                    StatusBadge(t.verifikasi)
                                }
                                Text(
                                    if (t.verifikasi == "pending") "Menunggu pemeriksaan petugas Bank Sampah." else "Alasan: " + (t.catatanVerifikasi ?: "-"),
                                    color = Slate500, fontSize = 12.sp,
                                )
                            }
                        }
                    }
                    resmi.forEach { t ->
                        EcoCard {
                            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                                Row(Modifier.weight(1f), verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                    androidx.compose.material3.Icon(Lucide.MapPin, contentDescription = null, tint = Emerald, modifier = Modifier.size(18.dp))
                                    Text(t.namaLokasi ?: "Lokasi", fontWeight = FontWeight.SemiBold)
                                }
                                StatusBadge(t.statusPanen)
                            }
                            Text("Terakhir diisi: " + tanggal(t.terakhirDiisiAt, withTime = false) + " · Estimasi panen: " + tanggal(t.estimasiPanenAt, withTime = false), color = Slate500, fontSize = 12.sp)
                            if (t.bioporiprint) {
                                Text(
                                    "BioporiPrint", color = Emerald, fontWeight = FontWeight.SemiBold, fontSize = 12.sp,
                                    modifier = Modifier.background(EmeraldSoft, androidx.compose.foundation.shape.RoundedCornerShape(50)).padding(horizontal = 10.dp, vertical = 4.dp),
                                )
                            }
                        }
                    }
                }
            }
        }
    }
}

/** Menjelaskan hubungan Organik dan metode (Biopori / BioporiPrint); sama dengan OrganikFlow di Web. */
@Composable
private fun OrganikFlow() {
    val langkah = listOf(
        Triple(Lucide.Recycle, "Organik", "Satu fitur untuk semua aktivitas sampah organik. Tidak menjadi saldo rupiah."),
        Triple(Lucide.Leaf, "Pilih metode", "Biopori atau BioporiPrint, isi data dan unggah foto bukti."),
        Triple(Lucide.Printer, "BioporiPrint", "Metode pipa biopori hasil 3D printing dari plastik warga yang didaur ulang."),
    )
    Column(
        Modifier.fillMaxWidth().background(EmeraldSoft.copy(alpha = 0.6f), androidx.compose.foundation.shape.RoundedCornerShape(20.dp)).padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(10.dp),
    ) {
        Text("Cara kerja jalur Organik", fontWeight = FontWeight.Bold, fontSize = 14.sp)
        langkah.forEach { (ikon, judul, teks) ->
            Row(
                Modifier.fillMaxWidth().background(androidx.compose.ui.graphics.Color.White, androidx.compose.foundation.shape.RoundedCornerShape(16.dp)).padding(14.dp),
                horizontalArrangement = Arrangement.spacedBy(12.dp),
            ) {
                androidx.compose.foundation.layout.Box(
                    Modifier.size(40.dp).background(EmeraldSoft, androidx.compose.foundation.shape.RoundedCornerShape(12.dp)),
                    contentAlignment = Alignment.Center,
                ) {
                    androidx.compose.material3.Icon(ikon, contentDescription = null, tint = Emerald, modifier = Modifier.size(20.dp))
                }
                Column(Modifier.weight(1f)) {
                    Text(judul, fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                    Text(teks, color = Slate500, fontSize = 12.sp)
                }
            }
        }
    }
}

@Composable
private fun SetoranOrganikRow(o: TransaksiOrganikDto) {
    EcoCard {
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f)) {
                Text(o.jenisOrganik ?: "Setoran organik", fontWeight = FontWeight.SemiBold)
                Text("${kg(o.beratKg)} · Setoran petugas", color = Slate500, fontSize = 13.sp)
                Text(tanggal(o.tanggal, withTime = false), color = Slate500, fontSize = 12.sp)
            }
            StatusBadge(o.statusPengolahan)
        }
        LabelValue("Estimasi kompos", kg(o.estimasiKomposKg))
    }
}

/** Membuka titik di aplikasi peta bawaan perangkat (intent geo:, tanpa izin lokasi dan tanpa SDK peta). */
@Composable
private fun BukaDiPeta(nama: String, lat: Double, lng: Double) {
    val context = androidx.compose.ui.platform.LocalContext.current
    Row(
        Modifier.fillMaxWidth().padding(top = 6.dp)
            .background(EmeraldSoft, androidx.compose.foundation.shape.RoundedCornerShape(14.dp))
            .clickable {
                val uri = android.net.Uri.parse("geo:0,0?q=" + lat + "," + lng + "(" + android.net.Uri.encode(nama) + ")")
                try {
                    context.startActivity(android.content.Intent(android.content.Intent.ACTION_VIEW, uri))
                } catch (_: android.content.ActivityNotFoundException) {
                    android.widget.Toast.makeText(context, "Tidak ada aplikasi peta di perangkat ini.", android.widget.Toast.LENGTH_SHORT).show()
                }
            }
            .padding(horizontal = 14.dp, vertical = 11.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(8.dp),
    ) {
        androidx.compose.material3.Icon(Lucide.MapPin, contentDescription = null, tint = Emerald, modifier = Modifier.size(18.dp))
        Text("Buka di peta", color = Emerald, fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
    }
}
