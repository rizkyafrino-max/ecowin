package id.ecowin.app

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyListScope
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.AccountBox
import androidx.compose.material.icons.filled.Favorite
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.ShoppingCart
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.NavigationBarItemDefaults
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

private enum class Tab(val label: String, val icon: ImageVector) {
    Beranda("Beranda", Icons.Filled.Home),
    Transaksi("Transaksi", Icons.Filled.ShoppingCart),
    Organik("Organik", Icons.Filled.Favorite),
    Saldo("Saldo", Icons.Filled.AccountBox),
    Profil("Profil", Icons.Filled.Person),
}

@Composable
fun MainScreen(user: UserDto, client: ApiClient, onUserChanged: (UserDto) -> Unit, onLogout: () -> Unit) {
    var tab by rememberSaveable { mutableIntStateOf(0) }
    var refreshKey by rememberSaveable { mutableIntStateOf(0) }
    val current = Tab.entries[tab]

    Scaffold(
        containerColor = Canvas,
        bottomBar = {
            NavigationBar(containerColor = androidx.compose.ui.graphics.Color.White) {
                Tab.entries.forEachIndexed { index, item ->
                    NavigationBarItem(
                        selected = index == tab,
                        onClick = { tab = index },
                        icon = { Icon(item.icon, contentDescription = item.label) },
                        label = { Text(item.label, fontSize = 10.sp, maxLines = 1) },
                        colors = NavigationBarItemDefaults.colors(indicatorColor = EmeraldSoft, selectedIconColor = Emerald, selectedTextColor = Emerald),
                    )
                }
            }
        },
    ) { padding ->
        Column(Modifier.fillMaxSize().padding(padding)) {
            Row(
                Modifier.fillMaxWidth().padding(start = 16.dp, end = 4.dp, top = 12.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween,
            ) {
                Column {
                    Text(current.label, fontSize = 22.sp, fontWeight = FontWeight.ExtraBold, color = Slate900)
                    Text(user.nasabah?.bankSampah?.nama ?: "EcoWin", fontSize = 13.sp, color = Slate500)
                }
                IconButton(onClick = { refreshKey++ }) { Icon(Icons.Filled.Refresh, contentDescription = "Muat ulang") }
            }

            when (current) {
                Tab.Beranda -> BerandaTab(client, user, refreshKey)
                Tab.Transaksi -> TransaksiTab(client, refreshKey)
                Tab.Organik -> OrganikTab(client, refreshKey)
                Tab.Saldo -> SaldoTab(client, refreshKey)
                Tab.Profil -> ProfilTab(client, user, refreshKey, onUserChanged, onLogout)
            }
        }
    }
}

/** Daftar dengan padding seragam untuk setiap tab. */
@Composable
fun TabList(content: LazyListScope.() -> Unit) {
    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
        content = content,
    )
}
