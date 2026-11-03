<?php
// judul     : tulisan di layar
// view      : nama view di MySQL
// sembunyi  : kolom yang tidak ditampilkan di tabel
// sekarang  : kolom yang otomatis diisi waktu saat ini
// pilihan   : kolom relasi => [view_sumber, kolom_id, kolom_nama]
// baca_saja : true = hanya tampil tabel, tanpa form tambah
return [
    'satuan' => ['judul' => 'Satuan', 'view' => 'v_satuan'],
    'vendor' => ['judul' => 'Vendor', 'view' => 'v_vendor'],
    'role'   => ['judul' => 'Role',   'view' => 'v_role'],
    'user' => [
        'judul'    => 'User',
        'view'     => 'v_user',
        'sembunyi' => ['password'],
        'pilihan'  => ['idrole' => ['v_role', 'idrole', 'nama_role']],
    ],
    'margin' => [
        'judul'    => 'Margin Penjualan',
        'view'     => 'v_margin_penjualan',
        'sekarang' => ['created_at', 'updated_at'],
        'pilihan'  => ['iduser' => ['v_user', 'iduser', 'username']],
    ],
    'barang' => [
        'judul'   => 'Barang',
        'view'    => 'v_barang',
        'pilihan' => ['idsatuan' => ['v_satuan', 'idsatuan', 'nama_satuan']],
    ],

    // ---- tabel transaksi (hanya lihat, isinya dibuat lewat halaman Transaksi) ----
    'pengadaan' => [
        'judul' => 'Pengadaan', 'view' => 'v_pengadaan', 'baca_saja' => true,
        'pilihan' => [
            'user_iduser'     => ['v_user', 'iduser', 'username'],
            'vendor_idvendor' => ['v_vendor', 'idvendor', 'nama_vendor'],
        ],
    ],
    'detail_pengadaan' => [
        'judul' => 'Detail Pengadaan', 'view' => 'v_detail_pengadaan', 'baca_saja' => true,
        'pilihan' => ['idbarang' => ['v_barang', 'idbarang', 'nama']],
    ],
    'penerimaan' => [
        'judul' => 'Penerimaan', 'view' => 'v_penerimaan', 'baca_saja' => true,
        'pilihan' => ['iduser' => ['v_user', 'iduser', 'username']],
    ],
    'detail_penerimaan' => [
        'judul' => 'Detail Penerimaan', 'view' => 'v_detail_penerimaan', 'baca_saja' => true,
        'pilihan' => ['barang_idbarang' => ['v_barang', 'idbarang', 'nama']],
    ],
    'penjualan' => [
        'judul' => 'Penjualan', 'view' => 'v_penjualan', 'baca_saja' => true,
        'pilihan' => ['iduser' => ['v_user', 'iduser', 'username']],
    ],
    'detail_penjualan' => [
        'judul' => 'Detail Penjualan', 'view' => 'v_detail_penjualan', 'baca_saja' => true,
        'pilihan' => ['idbarang' => ['v_barang', 'idbarang', 'nama']],
    ],
    'kartu_stok' => [
        'judul' => 'Kartu Stok', 'view' => 'v_kartu_stok', 'baca_saja' => true,
        'pilihan' => ['idbarang' => ['v_barang', 'idbarang', 'nama']],
    ],
    'retur' => [
        'judul' => 'Retur', 'view' => 'v_retur', 'baca_saja' => true,
        'pilihan' => ['iduser' => ['v_user', 'iduser', 'username']],
    ],
    'detail_retur' => [
        'judul' => 'Detail Retur', 'view' => 'v_detail_retur', 'baca_saja' => true,
    ],
];