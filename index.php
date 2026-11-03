<?php
$master = require 'daftar_master.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Menu Utama</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h3 class="mb-3">Transaksi</h3>
        <a href="transaksi.php" class="btn btn-warning w-100 py-4 fs-5 mb-4">
            Pengadaan, Penerimaan, Penjualan &amp; Stok
        </a>

        <h3 class="mb-3">Data Master dan Tabel Lainnya</h3>
        <div class="row g-3">
            <?php foreach ($master as $kunci => $m) { ?>
            <div class="col-sm-6 col-md-4">
                <a href="master.php?t=<?= $kunci ?>"
                   class="btn <?= empty($m['baca_saja']) ? 'btn-success' : 'btn-outline-success' ?> w-100 py-3 fs-5">
                    <?= htmlspecialchars($m['judul']) ?>
                </a>
            </div>
            <?php } ?>
        </div>
    </div>
</body>
</html>