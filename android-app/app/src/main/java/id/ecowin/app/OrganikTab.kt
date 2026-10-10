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
    var localRefresh by remember { mutableStateOf(0) }
    val setoran = rememberLoadable(client, refreshKey, localRefresh) { client.api.organik().data }
    val aktivitas = rememberLoadable(client, refreshKey, localRefresh) { client.api.aktivitasBiopori().data }
    val lokasi = rememberLoadable(client, refreshKey) { client.api.lokasiBiopori().data }

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
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp), modifier = Modifier.fillMaxWidth()) {
                StatTile("Total Organik", kg(totalSetoran + totalAktivitas), Modifier.weight(1f), icon = Lucide.Recycle)
                StatTile("Estimasi kompos", kg(totalKompos), Modifier.weight(1f), icon = Lucide.Leaf)
            }
        }

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

        item {
            LoadableContent(lokasi) { list ->
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    SectionTitle("Lokasi BioporiPrint & estimasi panen")
                    val titik = list.filter { it.bioporiprint }
                    if (titik.isEmpty()) EmptyText("Belum ada lokasi BioporiPrint di Bank Sampah Anda.")
                    titik.forEach { t ->
                        EcoCard {
                            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                                Text(t.namaLokasi ?: "Lokasi", fontWeight = FontWeight.SemiBold, modifier = Modifier.weight(1f))
                                StatusBadge(t.statusPanen)
                            }
                            LabelValue("Terakhir diisi", tanggal(t.terakhirDiisiAt, withTime = false))
                            LabelValue("Estimasi panen", tanggal(t.estimasiPanenAt, withTime = false))
                            if (t.latitude != null && t.longitude != null) BukaDiPeta(t.namaLokasi ?: "Lokasi BioporiPrint", t.latitude, t.longitude)
                        }
                    }
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
