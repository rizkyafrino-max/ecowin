package id.ecowin.app

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ColumnScope
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
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
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

/*
 * Komponen yang meniru komponen Web: kartu daftar berpembatas (PagedList), baris transaksi (TransactionCard),
 * baris aktivitas (ActivityCard), dan kartu saldo hijau (BalanceCard).
 */

/** Kartu putih berisi baris-baris daftar yang dipisah garis tipis. */
@Composable
fun ListCard(content: @Composable ColumnScope.() -> Unit) {
    Column(
        Modifier.fillMaxWidth()
            .background(Color.White, RoundedCornerShape(20.dp))
            .border(1.dp, Slate200, RoundedCornerShape(20.dp))
            .padding(horizontal = 14.dp, vertical = 4.dp),
        content = content,
    )
}

@Composable
fun RowDivider() {
    Box(Modifier.fillMaxWidth().height(1.dp).background(Slate200.copy(alpha = 0.7f)))
}

/** Daftar dengan pembatas antar baris; menampilkan teks kosong bila tidak ada isi. */
@Composable
fun <T> ListCardOf(items: List<T>, empty: String, row: @Composable (T) -> Unit) {
    ListCard {
        if (items.isEmpty()) EmptyText(empty)
        items.forEachIndexed { i, item ->
            if (i > 0) RowDivider()
            row(item)
        }
    }
}

/** Baris transaksi: kotak ikon, judul, keterangan, nominal berwarna, dan badge status. */
@Composable
fun TransactionRow(title: String, subtitle: String?, amount: String, kredit: Boolean, status: String? = null, onClick: (() -> Unit)? = null) {
    Row(
        Modifier.fillMaxWidth().let { if (onClick != null) it.clickable(onClick = onClick) else it }.padding(vertical = 12.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(14.dp),
    ) {
        Box(Modifier.size(44.dp).background(if (kredit) EmeraldSoft else AmberSoft, RoundedCornerShape(16.dp)), contentAlignment = Alignment.Center) {
            Icon(if (kredit) Lucide.ArrowDownLeft else Lucide.ArrowUpRight, contentDescription = null, tint = if (kredit) Emerald else Amber, modifier = Modifier.size(20.dp))
        }
        Column(Modifier.weight(1f)) {
            Text(title, fontWeight = FontWeight.SemiBold, color = Slate900, maxLines = 1, overflow = TextOverflow.Ellipsis)
            if (!subtitle.isNullOrBlank()) Text(subtitle, color = Slate500, fontSize = 12.sp, maxLines = 1, overflow = TextOverflow.Ellipsis)
        }
        Column(horizontalAlignment = Alignment.End, verticalArrangement = Arrangement.spacedBy(4.dp)) {
            Text((if (kredit) "+" else "−") + amount, fontWeight = FontWeight.Bold, color = if (kredit) Emerald else Slate900)
            if (status != null) StatusBadge(status)
        }
    }
}

/** Baris aktivitas organik: kotak ikon hijau muda, judul, keterangan, berat, dan status. */
@Composable
fun ActivityRow(icon: ImageVector, title: String, subtitle: String?, right: String? = null, status: String? = null, onClick: (() -> Unit)? = null) {
    Row(
        Modifier.fillMaxWidth().let { if (onClick != null) it.clickable(onClick = onClick) else it }.padding(vertical = 12.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(14.dp),
    ) {
        Box(Modifier.size(44.dp).background(EmeraldSoft, RoundedCornerShape(16.dp)), contentAlignment = Alignment.Center) {
            Icon(icon, contentDescription = null, tint = Emerald, modifier = Modifier.size(20.dp))
        }
        Column(Modifier.weight(1f)) {
            Text(title, fontWeight = FontWeight.SemiBold, color = Slate900, maxLines = 1, overflow = TextOverflow.Ellipsis)
            if (!subtitle.isNullOrBlank()) Text(subtitle, color = Slate500, fontSize = 12.sp, maxLines = 1, overflow = TextOverflow.Ellipsis)
        }
        Column(horizontalAlignment = Alignment.End, verticalArrangement = Arrangement.spacedBy(4.dp)) {
            if (right != null) Text(right, fontWeight = FontWeight.Bold, fontSize = 14.sp, color = Slate900)
            if (status != null) StatusBadge(status)
        }
    }
}

/** Kartu saldo hijau dengan lingkaran dekoratif, seperti BalanceCard di Web. */
@Composable
fun BalanceCard(saldo: Double, detail: String, actionLabel: String? = null, onAction: (() -> Unit)? = null) {
    Box(
        Modifier.fillMaxWidth()
            .background(Brush.linearGradient(listOf(Emerald, Color(0xFF10B981))), RoundedCornerShape(24.dp)),
    ) {
        Box(Modifier.align(Alignment.TopEnd).size(120.dp).background(Color.White.copy(alpha = 0.10f), CircleShape))
        Column(Modifier.padding(20.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
            Text("Saldo EcoWin", color = Color(0xFFD1FAE5), fontSize = 14.sp)
            Text(rupiah(saldo), color = Color.White, fontSize = 32.sp, fontWeight = FontWeight.ExtraBold)
            Text(detail, color = Color(0xFFD1FAE5), fontSize = 12.sp)
            if (actionLabel != null && onAction != null) {
                Row(
                    Modifier.padding(top = 12.dp)
                        .background(Color.White.copy(alpha = 0.18f), RoundedCornerShape(999.dp))
                        .border(1.dp, Color.White.copy(alpha = 0.3f), RoundedCornerShape(999.dp))
                        .clickable(onClick = onAction)
                        .padding(horizontal = 18.dp, vertical = 10.dp),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                ) {
                    Icon(Lucide.ArrowDownToLine, contentDescription = null, tint = Color.White, modifier = Modifier.size(16.dp))
                    Text(actionLabel, color = Color.White, fontWeight = FontWeight.SemiBold, fontSize = 14.sp)
                }
            }
        }
    }
}
