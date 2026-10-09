package id.ecowin.app

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

@Composable
fun OrganikTab(client: ApiClient, refreshKey: Int) {
    val state = rememberLoadable(client, refreshKey) { client.api.organik().data }

    TabList {
        item {
            EcoCard {
                Text("Jalur Organik", fontWeight = FontWeight.Bold)
                Text(
                    "Sampah organik tidak dikonversi menjadi saldo. Setoran diolah menjadi kompos, " +
                        "termasuk melalui lubang Biopori (BioporiPrint) di menu Biopori.",
                    color = Slate500, fontSize = 13.sp,
                )
            }
        }
        item {
            LoadableContent(state) { list ->
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    if (list.isNotEmpty()) {
                        Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                            StatTile("Total berat", kg(list.sumOf { it.beratKg }), Modifier.weight(1f))
                            StatTile("Estimasi kompos", kg(list.sumOf { it.estimasiKomposKg }), Modifier.weight(1f))
                        }
                    }
                    SectionTitle("Riwayat setoran organik")
                    if (list.isEmpty()) EmptyText("Belum ada setoran organik.")
                    list.forEach { o ->
                        EcoCard {
                            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                                Column(Modifier.weight(1f)) {
                                    Text(o.jenisOrganik ?: "Organik", fontWeight = FontWeight.SemiBold)
                                    Text("${kg(o.beratKg)} · ${o.metodePengolahan ?: "-"}", color = Slate500, fontSize = 13.sp)
                                    Text(tanggal(o.tanggal, withTime = false), color = Slate500, fontSize = 12.sp)
                                }
                                StatusBadge(o.statusPengolahan)
                            }
                            LabelValue("Estimasi kompos", kg(o.estimasiKomposKg))
                        }
                    }
                }
            }
        }
    }
}
