package id.ecowin.app

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.draw.clip
import androidx.compose.animation.fadeIn
import androidx.compose.animation.fadeOut
import androidx.compose.animation.slideInVertically
import androidx.compose.animation.slideOutVertically
import androidx.compose.foundation.BorderStroke
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.foundation.layout.offset
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyListScope
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.input.nestedscroll.NestedScrollConnection
import androidx.compose.ui.input.nestedscroll.NestedScrollSource
import androidx.compose.ui.input.nestedscroll.nestedScroll
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.saveable.rememberSaveable
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp

/**
 * Menu Nasabah sama dengan Web: Beranda, Transaksi, Organik, Saldo, Profil. Footer melayang dengan tombol
 * tengah "Semua Fitur"; Profil dibuka lewat foto/inisial di kanan atas. Biopori/BioporiPrint ada di dalam Organik.
 */
enum class Tab(val label: String, val icon: ImageVector) {
    Beranda("Beranda", Lucide.Home),
    Transaksi("Transaksi", Lucide.ArrowLeftRight),
    Fitur("Semua Fitur", Lucide.LayoutGrid),
    Organik("Organik", Lucide.Recycle),
    Saldo("Saldo", Lucide.Wallet),
    Profil("Profil", Lucide.UserRound),
}

private val FooterLeft = listOf(Tab.Beranda, Tab.Transaksi)
private val FooterRight = listOf(Tab.Organik, Tab.Saldo)

@Composable
fun MainScreen(user: UserDto, client: ApiClient, onUserChanged: (UserDto) -> Unit, onLogout: () -> Unit) {
    var tab by rememberSaveable { mutableIntStateOf(0) }
    var refreshKey by rememberSaveable { mutableIntStateOf(0) }
    val current = Tab.entries[tab]
    val go: (Tab) -> Unit = { tab = it.ordinal }
    var footerVisible by remember { mutableStateOf(true) }
    val scrollWatcher = remember {
        object : NestedScrollConnection {
            override fun onPreScroll(available: Offset, source: NestedScrollSource): Offset {
                if (available.y < -6f) footerVisible = false else if (available.y > 6f) footerVisible = true
                return Offset.Zero
            }
        }
    }
    LaunchedEffect(tab) { footerVisible = true }

    Scaffold(containerColor = Canvas) { padding ->
        Box(Modifier.fillMaxSize().padding(padding)) {
            Column(Modifier.fillMaxSize().nestedScroll(scrollWatcher)) {
                TopBar(user, current, onRefresh = { refreshKey++ }, onProfile = { go(Tab.Profil) })

                when (current) {
                    Tab.Beranda -> BerandaTab(client, user, refreshKey, go)
                    Tab.Transaksi -> TransaksiTab(client, refreshKey)
                    Tab.Fitur -> FiturTab(go)
                    Tab.Organik -> OrganikTab(client, refreshKey)
                    Tab.Saldo -> SaldoTab(client, user, refreshKey)
                    Tab.Profil -> ProfilTab(client, user, refreshKey, onUserChanged, onLogout)
                }
            }

            AnimatedVisibility(
                visible = footerVisible,
                modifier = Modifier.align(Alignment.BottomCenter),
                enter = slideInVertically { it } + fadeIn(),
                exit = slideOutVertically { it } + fadeOut(),
            ) {
                val density = androidx.compose.ui.platform.LocalDensity.current
                androidx.compose.runtime.CompositionLocalProvider(
                    androidx.compose.ui.platform.LocalDensity provides androidx.compose.ui.unit.Density(density.density, minOf(density.fontScale, 1.1f)),
                ) { FloatingFooter(current, go) }
            }
        }
    }
}

@Composable
private fun TopBar(user: UserDto, current: Tab, onRefresh: () -> Unit, onProfile: () -> Unit) {
    Row(
        Modifier.fillMaxWidth().padding(start = 20.dp, end = 8.dp, top = 12.dp, bottom = 4.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.SpaceBetween,
    ) {
        if (current == Tab.Beranda) {
            Row {
                Text("Eco", fontSize = 22.sp, fontWeight = FontWeight.ExtraBold, color = Slate900)
                Text("Win", fontSize = 22.sp, fontWeight = FontWeight.ExtraBold, color = Emerald)
            }
        } else {
            Column {
                Text(current.label, fontSize = 22.sp, fontWeight = FontWeight.ExtraBold, color = Slate900)
                subjudul(current)?.let { Text(it, fontSize = 13.sp, color = Slate500, modifier = Modifier.padding(end = 8.dp)) }
            }
        }
        Row(verticalAlignment = Alignment.CenterVertically) {
            IconButton(onClick = onRefresh) { Icon(Lucide.RefreshCw, contentDescription = "Muat ulang", tint = Slate500, modifier = Modifier.size(22.dp)) }
            Avatar(user.nama, Modifier.padding(end = 8.dp), size = 38, onClick = onProfile, url = user.avatar)
        }
    }
}

/** Pemuat foto profil Google: https + host Google saja, tanpa kredensial, ukuran dibatasi, di-cache di memori. */
private object AvatarCache {
    private val cache = object : android.util.LruCache<String, androidx.compose.ui.graphics.ImageBitmap>(8) {}

    fun get(url: String) = cache.get(url)

    suspend fun load(url: String): androidx.compose.ui.graphics.ImageBitmap? {
        cache.get(url)?.let { return it }
        return kotlinx.coroutines.withContext(kotlinx.coroutines.Dispatchers.IO) {
            runCatching {
                val conn = (java.net.URL(url).openConnection() as java.net.HttpURLConnection).apply {
                    connectTimeout = 8000
                    readTimeout = 8000
                    instanceFollowRedirects = false
                }
                try {
                    if (conn.responseCode != 200 || conn.contentLength > 1_000_000) return@runCatching null
                    conn.inputStream.use { input ->
                        val bytes = input.readBytes()
                        android.graphics.BitmapFactory.decodeByteArray(bytes, 0, bytes.size)?.asImageBitmap()
                    }
                } finally {
                    conn.disconnect()
                }
            }.getOrNull()?.also { cache.put(url, it) }
        }
    }
}

/** Lingkaran berisi foto profil Google (bila ada dan aman), jika tidak inisial nama (sama dengan Web). */
@Composable
fun Avatar(nama: String?, modifier: Modifier = Modifier, size: Int = 44, onClick: (() -> Unit)? = null, url: String? = null) {
    val aman = safeAvatarUrl(url)
    var foto by remember(aman) { mutableStateOf(aman?.let { AvatarCache.get(it) }) }
    androidx.compose.runtime.LaunchedEffect(aman) {
        if (aman != null && foto == null) foto = AvatarCache.load(aman)
    }
    Box(
        modifier.size(size.dp).clip(CircleShape).background(EmeraldSoft, CircleShape).let { if (onClick != null) it.clickable(onClick = onClick) else it },
        contentAlignment = Alignment.Center,
    ) {
        val f = foto
        if (f != null) {
            androidx.compose.foundation.Image(f, contentDescription = "Foto profil", contentScale = androidx.compose.ui.layout.ContentScale.Crop, modifier = Modifier.fillMaxSize())
        } else {
            Text(inisial(nama), color = Emerald, fontWeight = FontWeight.Bold, fontSize = (size * 0.36f).sp)
        }
    }
}

/** Footer melayang: dua menu, tombol tengah Semua Fitur, dua menu (sama dengan footer admin & Web). */
@Composable
private fun FloatingFooter(current: Tab, onSelect: (Tab) -> Unit, modifier: Modifier = Modifier) {
    Box(modifier.fillMaxWidth().navigationBarsPadding().padding(horizontal = 14.dp, vertical = 10.dp)) {
        Surface(
            modifier = Modifier.fillMaxWidth().shadow(10.dp, RoundedCornerShape(20.dp), ambientColor = Slate900.copy(alpha = 0.12f), spotColor = Slate900.copy(alpha = 0.12f)),
            shape = RoundedCornerShape(20.dp),
            color = Color.White.copy(alpha = 0.97f),
            border = BorderStroke(1.dp, Slate200),
        ) {
            Row(Modifier.heightIn(min = 64.dp).padding(horizontal = 6.dp), verticalAlignment = Alignment.CenterVertically) {
                FooterLeft.forEach { FooterItem(it, current == it, onSelect, Modifier.weight(1f)) }
                Box(Modifier.weight(1f))
                FooterRight.forEach { FooterItem(it, current == it, onSelect, Modifier.weight(1f)) }
            }
        }
        Box(
            Modifier
                .align(Alignment.TopCenter)
                .offset(y = (-22).dp)
                .size(58.dp)
                .background(Canvas, RoundedCornerShape(20.dp))
                .padding(5.dp)
                .background(if (current == Tab.Fitur) EmeraldDeep else Emerald, RoundedCornerShape(16.dp))
                .clickable { onSelect(Tab.Fitur) },
            contentAlignment = Alignment.Center,
        ) {
            Icon(Tab.Fitur.icon, contentDescription = Tab.Fitur.label, tint = Color.White, modifier = Modifier.size(24.dp))
        }
    }
}

@Composable
private fun FooterItem(tab: Tab, selected: Boolean, onSelect: (Tab) -> Unit, modifier: Modifier = Modifier) {
    Column(
        modifier.padding(horizontal = 2.dp).background(if (selected) EmeraldSoft else Color.Transparent, RoundedCornerShape(12.dp))
            .clickable { onSelect(tab) }.padding(vertical = 7.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.spacedBy(3.dp),
    ) {
        Icon(tab.icon, contentDescription = tab.label, tint = if (selected) Emerald else Slate500, modifier = Modifier.size(21.dp))
        Text(tab.label, fontSize = 10.sp, fontWeight = FontWeight.Bold, color = if (selected) Emerald else Slate500, maxLines = 1)
    }
}

/** Daftar dengan padding seragam untuk setiap tab (ruang bawah untuk footer melayang). */
@Composable
fun TabList(content: LazyListScope.() -> Unit) {
    LazyColumn(
        Modifier.fillMaxSize(),
        contentPadding = PaddingValues(start = 16.dp, end = 16.dp, top = 12.dp, bottom = 120.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
        content = content,
    )
}

private fun subjudul(tab: Tab): String? = when (tab) {
    Tab.Transaksi -> "Riwayat setoran sampah anorganik Anda."
    Tab.Organik -> "Kelola aktivitas organikmu. Organik tidak menjadi saldo rupiah."
    Tab.Saldo -> "Riwayat perubahan saldo EcoWin Anda."
    Tab.Profil -> "Data akun dan QR Card Anda."
    Tab.Fitur -> "Semua yang bisa Anda lakukan di EcoWin."
    else -> null
}
