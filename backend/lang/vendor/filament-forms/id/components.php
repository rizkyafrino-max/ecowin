<?php

return [
    'builder' => ['block_picker' => ['no_search_results_message' => 'Tidak ada blok yang cocok.', 'search_prompt' => 'Cari blok']],
    'color_picker' => ['panel_label' => 'Pemilih warna'],
    'date_time_picker' => [
        'month_select' => ['label' => 'Bulan'],
        'year_input' => ['label' => 'Tahun'],
        'hour_input' => ['label' => 'Jam'],
        'minute_input' => ['label' => 'Menit'],
        'second_input' => ['label' => 'Detik'],
    ],
    'file_upload' => [
        'actions' => ['download' => ['label' => 'Unduh'], 'open' => ['label' => 'Buka di tab baru']],
        'editor' => ['label' => 'Editor gambar'],
    ],
    'key_value' => ['columns' => ['actions' => ['label' => 'Aksi'], 'reorder' => ['label' => 'Urutkan']]],
    'repeater' => ['columns' => ['actions' => ['label' => 'Aksi'], 'reorder' => ['label' => 'Urutkan']]],
    'rich_editor' => [
        'actions' => ['close_panel' => ['label' => 'Tutup panel']],
        'custom_blocks' => [
            'actions' => ['delete' => ['label' => 'Hapus blok'], 'edit' => ['label' => 'Ubah blok']],
            'no_search_results_message' => 'Tidak ada blok yang cocok.',
            'search_label' => 'Cari blok',
            'search_prompt' => 'Cari blok',
        ],
        'toolbar' => ['label' => 'Bilah editor'],
    ],
    'select' => [
        'actions' => ['clear' => ['label' => 'Hapus pilihan'], 'remove_option' => ['label' => 'Hapus :label']],
        'search_label' => 'Cari',
    ],
    'tags_input' => ['tag_added' => 'Ditambahkan: :tag', 'tag_removed' => 'Dihapus: :tag'],
];
