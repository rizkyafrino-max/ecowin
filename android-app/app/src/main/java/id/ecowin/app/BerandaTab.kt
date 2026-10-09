package id.ecowin.app

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

@Composable
fun BerandaTab(client: ApiClient, user: UserDto, refreshKey: Int) {
    val state = rememberLoadable(client, refreshKey) { client.api.dashboard() }

    TabList {
        item {
            LoadableContent(state) { d ->
                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    if (user.nasabah?.statusVerifikasi == "pending") {
                        EcoCard {
                            Text("Akun menunggu verifikasi petugas", fontWeight = FontWeight.Bold, color = Amber)
                            Text("Petugas Bank Sampah Anda akan memeriksa data pendaftaran. Penarikan saldo aktif setelah akun diverifikasi.", color = Slate500, fontSize = 13.sp)
                        }
                    }
                    Column(
                        Modifier.fillMaxWidth().background(Emerald, RoundedCornerShape(18.dp)).padding(18.dp),
                    ) {
                        Text("Halo, ${d.nama ?: user.nama ?: "Nasabah"}", color = Color.White, fontSize = 15.sp)
                        Text("Saldo Anda", color = Color(0xFFD1FAE5), fontSize = 13.sp, modifier = Modifier.padding(top = 10.dp))
                        Text(rupiah(d.saldo ?: 0.0), color = Color.White, fontSize = 30.sp, fontWeight = FontWeight.ExtraBold)
                        Text(user.nasabah?.nomorNasabah ?: "", color = Color(0xFFD1FAE5), fontSize = 12.sp)
                    }

                    Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                        StatTile("Sampah anorganik", kg(d.anorganik?.totalBeratKg), Modifier.weight(1f))
                        StatTile("Sampah organik", kg(d.organik?.totalBeratKg), Modifier.weight(1f))
                    }
                    Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                        StatTile("Aktivitas organik", "${d.organik?.jumlahAktivitasBiopori ?: 0}", Modifier.weight(1f))
                        StatTile("Menunggu verifikasi", "${d.organik?.bioporiPending ?: 0}", Modifier.weight(1f))
                    }

                    SectionTitle("Transaksi terakhir")
                    val trx = d.transaksiTerakhir.orEmpty()
                    if (trx.isEmpty()) EmptyText("Belum ada transaksi.")
                    trx.forEach { TransaksiRow(it) }

                    SectionTitle("Aktivitas organik")
                    val bio = d.bioporiTerakhir.orEmpty()
                    if (bio.isEmpty()) EmptyText("Belum ada aktivitas organik.")
                    bio.forEach { BioporiRow(it) }
                }
            }
        }
    }
}

@Composable
fun TransaksiRow(t: TransaksiAnorganikDto) {
    EcoCard {
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f)) {
                Text(t.jenisSampah ?: "Sampah anorganik", fontWeight = FontWeight.SemiBold)
                Text("${kg(t.beratKg)} × ${rupiah(t.hargaPerKg)}/kg", color = Slate500, fontSize = 13.sp)
                Text(tanggal(t.createdAt), color = Slate500, fontSize = 12.sp)
            }
            Text("+" + rupiah(t.nilaiRupiah), color = Emerald, fontWeight = FontWeight.Bold)
        }
    }
}

@Composable
fun BioporiRow(a: AktivitasBioporiDto) {
    EcoCard {
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f)) {
                Text(a.lokasi ?: "Lokasi BioporiPrint", fontWeight = FontWeight.SemiBold)
                Text("${a.jenisSampah ?: "-"} · ${kg(a.beratKg)}", color = Slate500, fontSize = 13.sp)
                Text(tanggal(a.tanggalPemasukan), color = Slate500, fontSize = 12.sp)
            }
            StatusBadge(a.status)
        }
        if (!a.catatanPetugas.isNullOrBlank()) {
            Text("Catatan petugas: ${a.catatanPetugas}", color = Slate500, fontSize = 13.sp)
        }
    }
}
