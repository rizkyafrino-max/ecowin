package id.ecowin.app

import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.Image
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.selection.selectable
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

/** Kode jenis kelamin yang dikirim ke server: "L" atau "P". */
private val OpsiJenisKelamin = listOf(
    Triple("L", "Laki-laki", R.drawable.avatar_laki),
    Triple("P", "Perempuan", R.drawable.avatar_perempuan),
)

/** Pilihan jenis kelamin dengan pratinjau avatar bawaannya (dipakai di pendaftaran dan profil). */
@Composable
fun PilihJenisKelamin(terpilih: String?, onPilih: (String) -> Unit, modifier: Modifier = Modifier) {
    Column(modifier, verticalArrangement = Arrangement.spacedBy(6.dp)) {
        Text("Jenis kelamin", fontWeight = FontWeight.SemiBold, fontSize = 13.sp, color = Slate900)
        Row(horizontalArrangement = Arrangement.spacedBy(10.dp)) {
            OpsiJenisKelamin.forEach { (kode, label, gambar) ->
                val aktif = terpilih == kode
                Row(
                    Modifier
                        .weight(1f)
                        .clip(RoundedCornerShape(16.dp))
                        .background(if (aktif) EmeraldSoft else Color.White)
                        .border(BorderStroke(if (aktif) 2.dp else 1.dp, if (aktif) Emerald else Slate200), RoundedCornerShape(16.dp))
                        .selectable(selected = aktif, role = Role.RadioButton, onClick = { onPilih(kode) })
                        .padding(10.dp),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(10.dp),
                ) {
                    Image(painterResource(gambar), contentDescription = null, modifier = Modifier.size(40.dp).clip(CircleShape))
                    Text(label, fontWeight = FontWeight.SemiBold, fontSize = 14.sp, color = Slate900)
                }
            }
        }
        Text("Menentukan foto profil bawaan. Bisa diganti dengan foto sendiri.", fontSize = 12.sp, color = Slate500)
    }
}
