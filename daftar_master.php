<?php
// judul  : tulisan di layar
// view   : nama view di MySQL
// sembunyi : kolom yang tidak ditampilkan di tabel
// sekarang : kolom yang otomatis diisi waktu saat ini
// pilihan  : kolom relasi jadi dropdown => [view_sumber, kolom_id, kolom_nama]
return [
    'satuan' => [
        'judul' => 'Satuan',
        'view'  => 'v_satuan',
    ],
    'vendor' => [
        'judul' => 'Vendor',
        'view'  => 'v_vendor',
    ],
    'role' => [
        'judul' => 'Role',
        'view'  => 'v_role',
    ],
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
];