package id.ecowin.app

import android.widget.TextView
import org.osmdroid.views.MapView
import org.osmdroid.views.overlay.Marker
import org.osmdroid.views.overlay.infowindow.InfoWindow

/** Gelembung info penanda peta: judul, keterangan, dan pipa BioporiPrint berputar. Ketuk gelembung untuk menutup. */
class InfoWindowTitik(map: MapView) : InfoWindow(R.layout.info_titik, map) {
    override fun onOpen(item: Any?) {
        val marker = item as? Marker ?: return
        mView.findViewById<TextView>(R.id.info_judul).text = marker.title.orEmpty()
        mView.findViewById<TextView>(R.id.info_isi).text = marker.snippet.orEmpty()
        mView.setOnClickListener { close() }
    }

    override fun onClose() = Unit
}
