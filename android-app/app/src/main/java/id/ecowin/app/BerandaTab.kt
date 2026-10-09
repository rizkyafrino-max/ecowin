package id.ecowin.app

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Icon
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

@Composable
fun BerandaTab(client: ApiClient, user: UserDto, refreshKey: Int, onNavigate: (Tab) -> Unit) {
    val state = rememberLoadable(client, refreshKey) { client.api.dashboard() }
    val saldo = rememberLoadable(client, refreshKey) { client.api.saldo() }
    val statistik = rememberLoadable(client, refreshKey) { client.api.statistik(6) }

    TabList {
        item {
            Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(14.dp)) {
                Avatar(user.nama, size = 52)
                Column {
                    Text(sapaan() + ",", color = Slate500, fontSize = 14.sp)
                    Text(user.nama ?: "Nasabah", fontSize = 22.sp, fontWeight = FontWeight.ExtraBold, color = Slate900)
                    val bank = user.nasabah?.bankSampah
                    Text(listOfNotNull(bank?.nama, bank?.rt?.let { "RT $it/RW ${bank.rw}" }).joinToString(" · "), color = Slate500, fontSize = 12.sp)
                }
            }
        }

        if (user.nasabah?.statusVerifikasi == "pending") {
            item {
                Column(
                    Modifier.fillMaxWidth().background(AmberSoft, RoundedCornerShape(16.dp)).border(1.dp, Color(0xFFFDE68A), RoundedCornerShape(16.dp)).padding(14.dp),
                    verticalArrangement = Arrangement.spacedBy(4.dp),
                ) {
                    Row(verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                        Icon(Lucide.TriangleAlert, contentDescription = null, tint = Amber, modifier = Modifier.size(18.dp))
                        Text("Akun menunggu verifikasi petugas", fontWeight = FontWeight.Bold, color = Amber)
                    }
                    Text("Petugas Bank Sampah Anda akan memeriksa data pendaftaran. Penarikan saldo aktif setelah akun diverifikasi.", color = Amber, fontSize = 13.sp)
                }
            }
        }

        item {
            LoadableContent(state) { d ->
                val s = saldo.data
                BalanceCard(
                    saldo = d.saldo ?: 0.0,
                    detail = if (s != null) "Tersedia ${rupiah(s.saldoTersedia)} · Ditahan ${rupiah(s.saldoDitahan)}" else (user.nasabah?.nomorNasabah ?: ""),
                    onTarik = { onNavigate(Tab.Saldo) },
                )
            }
        }

        item { AksiCepat(onNavigate) }

        item {
            LoadableContent(state) { d ->
                Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                    Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                        StatTile("Total anorganik", kg(d.anorganik?.totalBeratKg), Modifier.weight(1f), icon = Lucide.Scale, hint = "${d.anorganik?.jumlahTransaksi ?: 0} transaksi")
                        StatTile("Total organik", kg(d.organik?.totalBeratKg), Modifier.weight(1f), icon = Lucide.Recycle, hint = "Estimasi kompos ${kg(d.organik?.estimasiKomposKg)}")
                    }
                    Row(horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                        StatTile("Aktivitas organik", "${d.organik?.jumlahAktivitasBiopori ?: 0}", Modifier.weight(1f), icon = Lucide.Leaf, hint = "${d.organik?.bioporiTerverifikasi ?: 0} terverifikasi")
                        StatTile("Menunggu verifikasi", "${d.organik?.bioporiPending ?: 0}", Modifier.weight(1f), icon = Lucide.Sprout, hint = "Aktivitas organik")
                    }

                    SectionTitle("Transaksi terbaru")
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

        item {
            EcoCard {
                Text("Setoran anorganik 6 bulan terakhir", fontWeight = FontWeight.Bold, color = Slate900)
                LoadableContent(statistik) { st -> GrafikBulanan(st) }
            }
        }
    }
}

private fun sapaan(): String = when (java.time.LocalTime.now().hour) {
    in 0..10 -> "Selamat pagi"
    in 11..14 -> "Selamat siang"
    in 15..17 -> "Selamat sore"
    else -> "Selamat malam"
}

/** Kartu saldo hijau dengan lingkaran dekoratif dan tombol Tarik saldo (sama dengan Web). */
@Composable
private fun BalanceCard(saldo: Double, detail: String, onTarik: () -> Unit) {
    Box(
        Modifier.fillMaxWidth()
            .background(Brush.linearGradient(listOf(Emerald, Color(0xFF10B981))), RoundedCornerShape(24.dp)),
    ) {
        Box(Modifier.align(Alignment.TopEnd).padding(top = 0.dp, end = 0.dp).size(120.dp).background(Color.White.copy(alpha = 0.10f), CircleShape))
        Column(Modifier.padding(20.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
            Text("Saldo EcoWin", color = Color(0xFFD1FAE5), fontSize = 14.sp)
            Text(rupiah(saldo), color = Color.White, fontSize = 32.sp, fontWeight = FontWeight.ExtraBold)
            Text(detail, color = Color(0xFFD1FAE5), fontSize = 12.sp)
            Text(
                "Tarik saldo",
                color = Color.White, fontWeight = FontWeight.SemiBold, fontSize = 14.sp,
                modifier = Modifier.padding(top = 12.dp)
                    .background(Color.White.copy(alpha = 0.18f), RoundedCornerShape(999.dp))
                    .border(1.dp, Color.White.copy(alpha = 0.3f), RoundedCornerShape(999.dp))
                    .clickable(onClick = onTarik)
                    .padding(horizontal = 18.dp, vertical = 10.dp),
            )
        }
    }
}

private data class Aksi(val tab: Tab, val icon: ImageVector, val label: String)

/** Aksi cepat bergaya pil (lingkaran ikon + label); pil pertama berwarna hijau. */
@Composable
private fun AksiCepat(onNavigate: (Tab) -> Unit) {
    val aksi = listOf(
        Aksi(Tab.Profil, Lucide.QrCode, "Setor sampah"),
        Aksi(Tab.Organik, Lucide.Recycle, "Organik"),
        Aksi(Tab.Saldo, Lucide.ArrowDownToLine, "Tarik saldo"),
        Aksi(Tab.Transaksi, Lucide.Scale, "Riwayat"),
    )
    EcoCard {
        Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween, verticalAlignment = Alignment.CenterVertically) {
            Text("Aksi cepat", fontWeight = FontWeight.Bold, color = Slate900)
            Text("Lihat semua", color = Slate500, fontSize = 12.sp, fontWeight = FontWeight.SemiBold, modifier = Modifier.clickable { onNavigate(Tab.Fitur) })
        }
        LazyRow(horizontalArrangement = Arrangement.spacedBy(10.dp), modifier = Modifier.padding(top = 6.dp)) {
            aksi.forEachIndexed { i, a ->
                item {
                    val bg = if (i == 0) Modifier.background(Brush.horizontalGradient(listOf(EmeraldSoft, Color(0xFF6EE7B7))), RoundedCornerShape(999.dp))
                    else Modifier.background(Color(0xFFF1F5F9), RoundedCornerShape(999.dp))
                    Row(
                        bg.clickable { onNavigate(a.tab) }.padding(start = 4.dp, end = 16.dp, top = 4.dp, bottom = 4.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                    ) {
                        Box(Modifier.size(44.dp).background(Color.White, CircleShape).border(1.dp, Slate200, CircleShape), contentAlignment = Alignment.Center) {
                            Icon(a.icon, contentDescription = null, tint = Emerald, modifier = Modifier.size(20.dp))
                        }
                        Text(a.label, fontWeight = FontWeight.SemiBold, fontSize = 14.sp, color = Slate900, maxLines = 1)
                    }
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

/** Grafik batang berat setoran per bulan (sama dengan grafik di Web): angka kg di atas batang, bulan kosong berupa garis tipis. */
@Composable
private fun GrafikBulanan(st: StatistikDto) {
    val max = (st.berat.maxOrNull() ?: 0.0).coerceAtLeast(1.0)
    Row(Modifier.fillMaxWidth().height(176.dp).padding(top = 8.dp), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
        st.berat.forEachIndexed { i, v ->
            Column(Modifier.weight(1f), horizontalAlignment = Alignment.CenterHorizontally, verticalArrangement = Arrangement.spacedBy(6.dp)) {
                Text(if (v > 0) kg(v).removeSuffix(" kg") + " kg" else "", fontSize = 9.sp, color = Slate500, fontWeight = FontWeight.SemiBold, maxLines = 1, modifier = Modifier.height(14.dp))
                Box(Modifier.weight(1f).fillMaxWidth(), contentAlignment = Alignment.BottomCenter) {
                    val fraksi = if (v > 0) (v / max).toFloat().coerceAtLeast(0.06f) else 0.02f
                    Box(
                        Modifier.fillMaxWidth().fillMaxHeight(fraksi)
                            .background(if (v > 0) Color(0xFF10B981) else Slate200, RoundedCornerShape(topStart = 12.dp, topEnd = 12.dp)),
                    )
                }
                Text(st.labels.getOrNull(i)?.substringBefore(' ') ?: "", fontSize = 10.sp, color = Slate500)
            }
        }
    }
    if (st.berat.all { it == 0.0 }) {
        Text("Belum ada setoran dalam 6 bulan terakhir.", color = Slate500, fontSize = 12.sp, modifier = Modifier.padding(top = 8.dp))
    }
}
