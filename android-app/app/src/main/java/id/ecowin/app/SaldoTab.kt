package id.ecowin.app

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.launch

@Composable
fun SaldoTab(client: ApiClient, refreshKey: Int) {
    var localRefresh by remember { mutableIntStateOf(0) }
    val saldo = rememberLoadable(client, refreshKey, localRefresh) { client.api.saldo() }
    val penarikan = rememberLoadable(client, refreshKey, localRefresh) { client.api.penarikan().data }
    val mutasi = rememberLoadable(client, refreshKey, localRefresh) { client.api.mutasi().data }
    val scope = rememberCoroutineScope()

    var jumlah by remember { mutableStateOf("") }
    var catatan by remember { mutableStateOf("") }
    var message by remember { mutableStateOf<String?>(null) }
    var success by remember { mutableStateOf(false) }
    var sending by remember { mutableStateOf(false) }

    TabList {
        item {
            LoadableContent(saldo) { s ->
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    EcoCard {
                        Text("Saldo saat ini", color = Slate500, fontSize = 13.sp)
                        Text(rupiah(s.saldo), fontSize = 28.sp, fontWeight = FontWeight.ExtraBold, color = Emerald)
                        LabelValue("Sedang diajukan", rupiah(s.saldoDitahan))
                        LabelValue("Dapat ditarik", rupiah(s.saldoTersedia))
                    }
                    Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                        StatTile("Total pemasukan", rupiah(s.totalPemasukan), Modifier.weight(1f))
                        StatTile("Total penarikan", rupiah(s.totalPenarikan), Modifier.weight(1f))
                    }
                }
            }
        }

        item {
            EcoCard {
                Text("Ajukan penarikan", fontWeight = FontWeight.Bold)
                OutlinedTextField(
                    jumlah, { jumlah = it.filter(Char::isDigit).take(9) },
                    label = { Text("Nominal (Rp)") }, singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number), modifier = Modifier.fillMaxWidth(),
                )
                OutlinedTextField(catatan, { catatan = it.take(500) }, label = { Text("Catatan (opsional)") }, modifier = Modifier.fillMaxWidth())
                message?.let { Text(it, color = if (success) Emerald else Red, fontSize = 13.sp) }
                PrimaryButton("Ajukan", loading = sending) {
                    val nominal = jumlah.toLongOrNull()
                    val tersedia = saldo.data?.saldoTersedia ?: 0.0
                    success = false
                    message = when {
                        nominal == null || nominal <= 0 -> "Masukkan nominal penarikan."
                        nominal > tersedia -> "Nominal melebihi saldo yang dapat ditarik."
                        else -> null
                    }
                    if (message != null || nominal == null) return@PrimaryButton

                    sending = true
                    scope.launch {
                        try {
                            client.api.ajukanPenarikan(PenarikanRequest(nominal, catatan.ifBlank { null }))
                            success = true
                            message = "Pengajuan terkirim dan menunggu persetujuan petugas."
                            jumlah = ""
                            catatan = ""
                            localRefresh++
                        } catch (e: Exception) {
                            message = client.errorMessage(e)
                        } finally {
                            sending = false
                        }
                    }
                }
            }
        }

        item {
            LoadableContent(penarikan) { list ->
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    SectionTitle("Riwayat penarikan")
                    if (list.isEmpty()) EmptyText("Belum ada pengajuan penarikan.")
                    list.forEach { p ->
                        EcoCard {
                            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
                                Column(Modifier.weight(1f)) {
                                    Text(rupiah(p.jumlah), fontWeight = FontWeight.SemiBold)
                                    Text(tanggal(p.createdAt), color = Slate500, fontSize = 12.sp)
                                }
                                StatusBadge(p.status)
                            }
                            if (!p.catatan.isNullOrBlank()) Text(p.catatan, color = Slate500, fontSize = 13.sp)
                        }
                    }
                }
            }
        }

        item {
            LoadableContent(mutasi) { list ->
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    SectionTitle("Histori saldo")
                    if (list.isEmpty()) EmptyText("Belum ada perubahan saldo.")
                    list.forEach { m ->
                        EcoCard {
                            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                Column(Modifier.weight(1f)) {
                                    Text(m.keterangan ?: m.tipe, fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                                    Text(tanggal(m.createdAt), color = Slate500, fontSize = 12.sp)
                                }
                                Text(
                                    (if (m.tipe == "kredit") "+" else "−") + rupiah(m.jumlah),
                                    color = if (m.tipe == "kredit") Emerald else Red,
                                    fontWeight = FontWeight.Bold,
                                )
                            }
                            Text("Saldo: ${rupiah(m.saldoSebelum)} → ${rupiah(m.saldoSesudah)}", color = Slate500, fontSize = 12.sp)
                        }
                    }
                }
            }
        }
    }
}
