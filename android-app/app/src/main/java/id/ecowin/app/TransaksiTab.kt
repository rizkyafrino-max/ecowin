package id.ecowin.app

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

@Composable
fun TransaksiTab(client: ApiClient, refreshKey: Int) {
    var showHarga by rememberSaveable { mutableStateOf(false) }
    val riwayat = rememberLoadable(client, refreshKey) { client.api.transaksiAnorganik().data }
    val harga = rememberLoadable(client, refreshKey) { client.api.harga().data }

    TabList {
        item {
            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                FilterChip(selected = !showHarga, onClick = { showHarga = false }, label = { Text("Riwayat setoran") })
                FilterChip(selected = showHarga, onClick = { showHarga = true }, label = { Text("Daftar harga") })
            }
        }

        if (!showHarga) {
            item {
                LoadableContent(riwayat) { list ->
                    Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                        if (list.isEmpty()) EmptyText("Belum ada setoran sampah anorganik.")
                        list.forEach { TransaksiRow(it) }
                    }
                }
            }
        } else {
            item {
                LoadableContent(harga) { kategori ->
                    Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                        Text("Harga diambil otomatis oleh sistem sesuai berat saat menyetor.", color = Slate500, fontSize = 13.sp)
                        kategori.forEach { k ->
                            SectionTitle(k.namaKategori)
                            k.jenisSampah.forEach { j ->
                                EcoCard {
                                    Text(j.namaJenis, fontWeight = FontWeight.SemiBold)
                                    if (j.harga.isEmpty()) EmptyText("Belum ada harga aktif.")
                                    j.harga.forEach { h ->
                                        val rentang = if (h.maksimalBerat != null) "${kg(h.minimalBerat)} – ${kg(h.maksimalBerat)}" else "≥ ${kg(h.minimalBerat)}"
                                        LabelValue(rentang, rupiah(h.hargaPerKg) + "/kg")
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
