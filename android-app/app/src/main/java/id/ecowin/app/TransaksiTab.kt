package id.ecowin.app

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

/** Transaksi: riwayat setoran anorganik (satu kartu daftar) lalu harga sampah saat ini, seperti halaman Transaksi di Web. */
@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun TransaksiTab(client: ApiClient, refreshKey: Int) {
    val riwayat = rememberLoadable(client, refreshKey) { client.api.transaksiAnorganik().data }
    val harga = rememberLoadable(client, refreshKey) { client.api.harga().data }
    var detail by remember { mutableStateOf<TransaksiAnorganikDto?>(null) }

    TabList {
        item {
            LoadableContent(riwayat) { list ->
                ListCardOf(list, "Belum ada transaksi. Setoran anorganik yang dicatat petugas akan muncul di sini.") { t ->
                    TransactionRow(
                        title = t.jenisSampah ?: "Setoran anorganik",
                        subtitle = listOf(kg(t.beratKg), tanggal(t.createdAt)).joinToString(" · "),
                        amount = rupiah(t.nilaiRupiah),
                        kredit = true,
                        onClick = { detail = t },
                    )
                }
            }
        }

        item {
            LoadableContent(harga) { kategori ->
                val jenis = kategori.flatMap { it.jenisSampah }.filter { it.harga.isNotEmpty() }
                if (jenis.isNotEmpty()) {
                    EcoCard {
                        Text("Harga sampah anorganik saat ini", fontWeight = FontWeight.Bold)
                        Text("Harga bertingkat mengikuti berat setoran dan dapat berubah sewaktu-waktu.", color = Slate500, fontSize = 12.sp)
                        Column(Modifier.padding(top = 8.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                            jenis.forEach { j ->
                                Column(Modifier.fillMaxWidth().background(Canvas, RoundedCornerShape(16.dp)).padding(14.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                                    Text(j.namaJenis, fontWeight = FontWeight.SemiBold)
                                    j.harga.forEach { h ->
                                        val rentang = if (h.maksimalBerat != null) "${h.minimalBerat.toInt()}–${h.maksimalBerat.toInt()} kg" else "≥ ${h.minimalBerat.toInt()} kg"
                                        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                            Text(rentang, color = Slate500, fontSize = 12.sp)
                                            Text(rupiah(h.hargaPerKg) + "/kg", fontSize = 12.sp, fontWeight = FontWeight.Bold)
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

    detail?.let { t ->
        ModalBottomSheet(onDismissRequest = { detail = null }, containerColor = androidx.compose.ui.graphics.Color.White) {
            Column(Modifier.navigationBarsPadding().padding(horizontal = 20.dp).padding(bottom = 24.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                Text("Detail transaksi", fontSize = 18.sp, fontWeight = FontWeight.Bold)
                LabelValue("Jenis sampah", t.jenisSampah ?: "-")
                LabelValue("Berat", kg(t.beratKg))
                LabelValue("Harga per kg", rupiah(t.hargaPerKg))
                LabelValue("Total", rupiah(t.nilaiRupiah))
                LabelValue("Waktu", tanggal(t.createdAt))
            }
        }
    }
}
