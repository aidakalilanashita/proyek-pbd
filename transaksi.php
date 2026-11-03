<?php
require 'koneksi.php';

// Memanggil stored procedure lewat prepared statement
function panggil($koneksi, $sql, $tipe, $param) {
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param($tipe, ...$param);
    $stmt->execute();
    $stmt->close();
    while ($koneksi->more_results()) {
        $koneksi->next_result();
    }
}

function ambil($koneksi, $sql) {
    return $koneksi->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function opsi($baris, $id, $teks) {
    foreach ($baris as $b) {
        echo '<option value="' . htmlspecialchars($b[$id]) . '">'
           . htmlspecialchars($b[$teks]) . '</option>';
    }
}

$pesan = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $aksi = $_POST['aksi'] ?? '';
        if ($aksi === 'pengadaan') {
            panggil($koneksi, "CALL sp_tambah_pengadaan(?,?,?,?)", 'iiii',
                [(int)$_POST['iduser'], (int)$_POST['idvendor'],
                 (int)$_POST['idbarang'], (int)$_POST['jumlah']]);
            $ok = 'Pengadaan berhasil dibuat (status Menunggu).';
        } elseif ($aksi === 'terima') {
            panggil($koneksi, "CALL sp_terima_pengadaan(?,?)", 'ii',
                [(int)$_POST['iduser'], (int)$_POST['idpengadaan']]);
            $ok = 'Penerimaan berhasil. Stok bertambah otomatis lewat trigger.';
        } elseif ($aksi === 'jual') {
            panggil($koneksi, "CALL sp_tambah_penjualan(?,?,?,?)", 'iiii',
                [(int)$_POST['iduser'], (int)$_POST['idmargin'],
                 (int)$_POST['idbarang'], (int)$_POST['jumlah']]);
            $ok = 'Penjualan berhasil. Stok berkurang otomatis lewat trigger.';
        } else {
            throw new Exception('Aksi tidak dikenal');
        }
        header('Location: transaksi.php?ok=' . urlencode($ok));
        exit;
    } catch (Exception $e) {
        $pesan = $e->getMessage();
    }
}

// Semua data dibaca lewat VIEW
$users    = ambil($koneksi, "SELECT iduser, username FROM v_user");
$vendors  = ambil($koneksi, "SELECT idvendor, nama_vendor FROM v_vendor");
$barangs  = ambil($koneksi, "SELECT idbarang, nama FROM v_barang");
$margins  = ambil($koneksi, "SELECT idmargin_penjualan, persen FROM v_margin_penjualan");
foreach ($margins as &$mg) { $mg['label'] = $mg['persen'] . '%'; }
unset($mg);
$menunggu = ambil($koneksi,
    "SELECT idpengadaan, CONCAT('#', idpengadaan, ' - total ', total_nilai) AS label
     FROM v_pengadaan WHERE status_pengadaan = 'P'");

// Pakai FUNCTION: stok dan harga jual untuk margin yang dipilih
$m = (int)($_GET['m'] ?? 0);
if ($m === 0 && $margins) { $m = (int)$margins[0]['idmargin_penjualan']; }
$stmt = $koneksi->prepare(
    "SELECT nama, harga, fn_stok_barang(idbarang) AS stok,
            fn_harga_jual(idbarang, ?) AS harga_jual
     FROM v_barang");
$stmt->bind_param('i', $m);
$stmt->execute();
$tabelBarang = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Efek TRIGGER: kartu stok terbaru
$kartu = ambil($koneksi,
    "SELECT k.idkartu_stok, b.nama, k.jenis_transaksi, k.masuk, k.keluar, k.stock, k.created_at
     FROM v_kartu_stok k JOIN v_barang b ON b.idbarang = k.idbarang
     ORDER BY k.idkartu_stok DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transaksi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="index.php">Menu Utama</a>
            <span class="navbar-text text-white">Transaksi</span>
        </div>
    </nav>

    <div class="container py-4">
        <?php if (isset($_GET['ok'])) { ?>
            <div class="alert alert-success"><?= htmlspecialchars($_GET['ok']) ?></div>
        <?php } ?>
        <?php if ($pesan) { ?>
            <div class="alert alert-danger"><?= htmlspecialchars($pesan) ?></div>
        <?php } ?>

        <div class="row g-3 mb-4">
            <!-- 1. Pengadaan -->
            <div class="col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-success text-white">1. Pengadaan (SP)</div>
                    <div class="card-body">
                        <form method="post" class="d-grid gap-2">
                            <input type="hidden" name="aksi" value="pengadaan">
                            <select name="iduser" class="form-select" required>
                                <option value="">-- user --</option><?php opsi($users, 'iduser', 'username'); ?>
                            </select>
                            <select name="idvendor" class="form-select" required>
                                <option value="">-- vendor --</option><?php opsi($vendors, 'idvendor', 'nama_vendor'); ?>
                            </select>
                            <select name="idbarang" class="form-select" required>
                                <option value="">-- barang --</option><?php opsi($barangs, 'idbarang', 'nama'); ?>
                            </select>
                            <input type="number" name="jumlah" min="1" class="form-control" placeholder="Jumlah" required>
                            <button class="btn btn-success">Buat Pengadaan</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 2. Penerimaan -->
            <div class="col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-success text-white">2. Penerimaan (SP + Trigger)</div>
                    <div class="card-body">
                        <form method="post" class="d-grid gap-2">
                            <input type="hidden" name="aksi" value="terima">
                            <select name="iduser" class="form-select" required>
                                <option value="">-- user --</option><?php opsi($users, 'iduser', 'username'); ?>
                            </select>
                            <select name="idpengadaan" class="form-select" required>
                                <option value="">-- pengadaan menunggu --</option><?php opsi($menunggu, 'idpengadaan', 'label'); ?>
                            </select>
                            <button class="btn btn-success">Terima Barang</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 3. Penjualan -->
            <div class="col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-success text-white">3. Penjualan (SP + Function + Trigger)</div>
                    <div class="card-body">
                        <form method="post" class="d-grid gap-2">
                            <input type="hidden" name="aksi" value="jual">
                            <select name="iduser" class="form-select" required>
                                <option value="">-- user --</option><?php opsi($users, 'iduser', 'username'); ?>
                            </select>
                            <select name="idmargin" class="form-select" required>
                                <option value="">-- margin --</option><?php opsi($margins, 'idmargin_penjualan', 'label'); ?>
                            </select>
                            <select name="idbarang" class="form-select" required>
                                <option value="">-- barang --</option><?php opsi($barangs, 'idbarang', 'nama'); ?>
                            </select>
                            <input type="number" name="jumlah" min="1" class="form-control" placeholder="Jumlah" required>
                            <button class="btn btn-success">Jual</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hasil FUNCTION -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white">Stok dan Harga Jual (hasil function)</div>
            <div class="card-body">
                <form method="get" class="row g-2 mb-3">
                    <div class="col-auto"><label class="col-form-label">Margin:</label></div>
                    <div class="col-auto">
                        <select name="m" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($margins as $mg) { ?>
                                <option value="<?= $mg['idmargin_penjualan'] ?>"
                                    <?= (int)$mg['idmargin_penjualan'] === $m ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($mg['label']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </form>
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-dark">
                        <tr><th>Barang</th><th>Harga Beli</th><th>Stok (fn_stok_barang)</th><th>Harga Jual (fn_harga_jual)</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tabelBarang as $r) { ?>
                        <tr>
                            <td><?= htmlspecialchars($r['nama']) ?></td>
                            <td><?= $r['harga'] ?></td>
                            <td><?= $r['stok'] ?></td>
                            <td><?= $r['harga_jual'] ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Efek TRIGGER -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">Kartu Stok Terbaru (diisi otomatis oleh trigger)</div>
            <div class="card-body">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-dark">
                        <tr><th>ID</th><th>Barang</th><th>Jenis</th><th>Masuk</th><th>Keluar</th><th>Stok</th><th>Waktu</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kartu as $r) { ?>
                        <tr>
                            <td><?= $r['idkartu_stok'] ?></td>
                            <td><?= htmlspecialchars($r['nama']) ?></td>
                            <td><?= $r['jenis_transaksi'] === 'M' ? 'Masuk' : 'Keluar' ?></td>
                            <td><?= $r['masuk'] ?></td>
                            <td><?= $r['keluar'] ?></td>
                            <td><?= $r['stock'] ?></td>
                            <td><?= $r['created_at'] ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>