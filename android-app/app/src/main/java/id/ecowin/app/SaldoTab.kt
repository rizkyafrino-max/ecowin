package id.ecowin.app

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.launch

/** Saldo: kartu saldo hijau, dua kartu angka, dan riwayat saldo. Tombol "Tarik saldo" membuka halaman Tarik Saldo (seperti Web). */
@Composable
fun SaldoTab(client: ApiClient, user: UserDto, refreshKey: Int) {
    var tarik by rememberSaveable { mutableStateOf(false) }
    if (tarik) {
        PenarikanScreen(client, user, refreshKey, onBack = { tarik = false })
        return
    }

    val saldo = rememberLoadable(client, refreshKey) { client.api.saldo() }
    val mutasi = rememberLoadable(client, refreshKey) { client.api.mutasi().data }

    TabList {
        item {
            LoadableContent(saldo) { s ->
                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    BalanceCard(
                        saldo = s.saldo,
                        detail = "Tersedia ${rupiah(s.saldoTersedia)}" + if (s.saldoDitahan > 0) " · Ditahan ${rupiah(s.saldoDitahan)} (penarikan diproses)" else "",
                        actionLabel = "Tarik saldo",
                        onAction = { tarik = true },
                    )
                    Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                        StatTile("Total pemasukan", rupiah(s.totalPemasukan), Modifier.weight(1f), icon = Lucide.ArrowDownToLine)
                        StatTile("Total penarikan", rupiah(s.totalPenarikan), Modifier.weight(1f), icon = Lucide.ArrowUpFromLine, warn = true)
                    }
                }
            }
        }

        item { Text("Riwayat saldo", fontWeight = FontWeight.Bold, modifier = Modifier.padding(top = 4.dp)) }
        item {
            LoadableContent(mutasi) { list ->
                ListCardOf(list, "Belum ada mutasi saldo. Setoran dan penarikan akan tercatat di sini.") { m ->
                    TransactionRow(
                        title = m.keterangan ?: if (m.tipe == "kredit") "Saldo masuk" else "Saldo keluar",
                        subtitle = "Saldo ${rupiah(m.saldoSesudah)} · ${tanggal(m.createdAt)}",
                        amount = rupiah(m.jumlah),
                        kredit = m.tipe == "kredit",
                    )
                }
            }
        }
    }
}

/** Halaman Tarik Saldo: saldo tersedia, nominal + pilihan cepat, catatan, konfirmasi, lalu riwayat penarikan. */
@Composable
private fun PenarikanScreen(client: ApiClient, user: UserDto, refreshKey: Int, onBack: () -> Unit) {
    var localRefresh by remember { mutableIntStateOf(0) }
    val saldo = rememberLoadable(client, refreshKey, localRefresh) { client.api.saldo() }
    val penarikan = rememberLoadable(client, refreshKey, localRefresh) { client.api.penarikan().data }
    val scope = rememberCoroutineScope()
    val belumVerifikasi = user.nasabah?.statusVerifikasi == "pending"

    var jumlah by remember { mutableStateOf("") }
    var catatan by remember { mutableStateOf("") }
    var message by remember { mutableStateOf<String?>(null) }
    var success by remember { mutableStateOf(false) }
    var confirm by remember { mutableStateOf(false) }
    var sending by remember { mutableStateOf(false) }
    val tersedia = saldo.data?.saldoTersedia

    TabList {
        item {
            TextButton(onClick = onBack) { Text("← Kembali ke Saldo", color = Emerald) }
            Text("Ajukan penarikan. Petugas akan memeriksa dan menyetujuinya.", color = Slate500, fontSize = 14.sp)
        }

        if (belumVerifikasi) {
            item {
                EcoCard {
                    Text("Akun menunggu verifikasi petugas", fontWeight = FontWeight.Bold, color = Amber)
                    Text("Penarikan saldo aktif setelah akun diverifikasi.", color = Amber, fontSize = 13.sp)
                }
            }
        }

        item {
            EcoCard {
                Text("Saldo tersedia", color = Slate500, fontSize = 13.sp)
                Text(if (tersedia == null) "…" else rupiah(tersedia), fontSize = 30.sp, fontWeight = FontWeight.ExtraBold, color = Emerald)
                OutlinedTextField(
                    jumlah, { jumlah = it.filter(Char::isDigit).take(9) },
                    label = { Text("Nominal penarikan (Rp)") }, placeholder = { Text("mis. 20000") }, singleLine = true,
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number), modifier = Modifier.fillMaxWidth().padding(top = 8.dp),
                )
                Row(horizontalArrangement = Arrangement.spacedBy(8.dp), modifier = Modifier.padding(vertical = 4.dp)) {
                    listOf(10000L, 20000L, 50000L).forEach { v ->
                        Text(
                            rupiah(v), color = Emerald, fontSize = 12.sp, fontWeight = FontWeight.SemiBold,
                            modifier = Modifier.background(EmeraldSoft, RoundedCornerShape(999.dp)).clickable { jumlah = v.toString() }.padding(horizontal = 14.dp, vertical = 7.dp),
                        )
                    }
                    if ((tersedia ?: 0.0) > 0) {
                        Text(
                            "Semua saldo", color = Slate500, fontSize = 12.sp, fontWeight = FontWeight.SemiBold,
                            modifier = Modifier.border(1.dp, Slate200, RoundedCornerShape(999.dp)).clickable { jumlah = tersedia!!.toLong().toString() }.padding(horizontal = 14.dp, vertical = 7.dp),
                        )
                    }
                }
                OutlinedTextField(catatan, { catatan = it.take(500) }, label = { Text("Catatan (opsional)") }, modifier = Modifier.fillMaxWidth())
                message?.let { Text(it, color = if (success) Emerald else Red, fontSize = 13.sp) }
                PrimaryButton("Ajukan penarikan", enabled = !belumVerifikasi) {
                    val nominal = jumlah.toLongOrNull()
                    success = false
                    message = when {
                        nominal == null || nominal <= 0 -> "Isi nominal penarikan."
                        nominal > (tersedia ?: 0.0) -> "Melebihi saldo tersedia (${rupiah(tersedia)})."
                        else -> null
                    }
                    if (message == null) confirm = true
                }
            }
        }

        item { Text("Riwayat penarikan", fontWeight = FontWeight.Bold) }
        item {
            LoadableContent(penarikan) { list ->
                ListCardOf(list, "Belum ada penarikan. Pengajuan penarikan Anda akan tampil di sini.") { p ->
                    TransactionRow("Penarikan saldo", p.catatan ?: tanggal(p.createdAt), rupiah(p.jumlah), kredit = false, status = p.status)
                }
            }
        }
    }

    if (confirm) {
        val nominal = jumlah.toLongOrNull() ?: 0L
        AlertDialog(
            onDismissRequest = { if (!sending) confirm = false },
            title = { Text("Ajukan penarikan?") },
            text = { Text("Anda akan mengajukan penarikan ${rupiah(nominal)}. Saldo dikurangi setelah petugas menyetujui.") },
            confirmButton = {
                TextButton(enabled = !sending, onClick = {
                    sending = true
                    scope.launch {
                        try {
                            client.api.ajukanPenarikan(PenarikanRequest(nominal, catatan.ifBlank { null }))
                            success = true
                            message = "Pengajuan terkirim, menunggu petugas."
                            jumlah = ""
                            catatan = ""
                            localRefresh++
                        } catch (e: Exception) {
                            success = false
                            message = client.errorMessage(e)
                        } finally {
                            sending = false
                            confirm = false
                        }
                    }
                }) { Text("Ajukan", color = Emerald, fontWeight = FontWeight.Bold) }
            },
            dismissButton = { TextButton(enabled = !sending, onClick = { confirm = false }) { Text("Batal") } },
        )
    }
}
