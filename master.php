<?php
require 'koneksi.php';
$master = require 'daftar_master.php';

$t = $_GET['t'] ?? 'satuan';
if (!isset($master[$t])) {
    die('Halaman tidak ditemukan');
}
$cfg      = $master[$t];
$judul    = $cfg['judul'];
$view     = $cfg['view'];
$sembunyi = $cfg['sembunyi'] ?? [];
$sekarang = $cfg['sekarang'] ?? [];
$pilihan  = $cfg['pilihan'] ?? [];
$pesan    = '';

// Data utama + daftar kolom
$hasil = $koneksi->query("SELECT * FROM `$view`");
$kolom = array_map(fn($f) => $f->name, $hasil->fetch_fields());

$kolomTampil = array_values(array_diff($kolom, $sembunyi));
$kolomForm   = array_values(array_diff(array_slice($kolom, 1), $sekarang)); // kolom 1 = ID otomatis

// Isi dropdown untuk kolom relasi
$opsi = [];
foreach ($pilihan as $k => [$vSumber, $kId, $kNama]) {
    $r = $koneksi->query("SELECT `$kId` AS id, `$kNama` AS nama FROM `$vSumber`");
    while ($b = $r->fetch_assoc()) {
        $opsi[$k][$b['id']] = $b['nama'];
    }
}

// Proses tambah data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nama  = array_merge($kolomForm, $sekarang);
        $nama  = array_map(fn($k) => "`$k`", $nama);
        $tanda = array_merge(
            array_fill(0, count($kolomForm), '?'),
            array_fill(0, count($sekarang), 'NOW()')
        );
        $sql  = "INSERT INTO `$view` (" . implode(',', $nama) . ") VALUES (" . implode(',', $tanda) . ")";
        $stmt = $koneksi->prepare($sql);

        $nilai = [];
        foreach ($kolomForm as $k) {
            $v = $_POST[$k] ?? '';
            if ($k === 'password') {
                $v = password_hash($v, PASSWORD_DEFAULT);
            }
            $nilai[] = $v;
        }
        if (count($nilai) > 0) {
            $stmt->bind_param(str_repeat('s', count($nilai)), ...$nilai);
        }
        $stmt->execute();
        header("Location: master.php?t=$t");
        exit;
    } catch (Exception $e) {
        $pesan = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data <?= htmlspecialchars($judul) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="index.php">Menu Utama</a>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($master as $kunci => $m) { ?>
                    <a href="master.php?t=<?= $kunci ?>"
                       class="btn btn-sm <?= $kunci === $t ? 'btn-light' : 'btn-outline-light' ?>">
                        <?= htmlspecialchars($m['judul']) ?>
                    </a>
                <?php } ?>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">Data <?= htmlspecialchars($judul) ?></h4>
            </div>
            <div class="card-body">

                <?php if ($pesan) { ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($pesan) ?></div>
                <?php } ?>

                <form method="post" class="row g-2 mb-4">
                    <?php foreach ($kolomForm as $k) { ?>
                        <div class="col-md">
                            <?php if ($k === 'status') { ?>
                                <select name="status" class="form-select">
                                    <option value="1">Aktif</option>
                                    <option value="0">Nonaktif</option>
                                </select>
                            <?php } elseif (isset($opsi[$k])) { ?>
                                <select name="<?= htmlspecialchars($k) ?>" class="form-select" required>
                                    <option value="">-- pilih <?= htmlspecialchars($k) ?> --</option>
                                    <?php foreach ($opsi[$k] as $id => $teks) { ?>
                                        <option value="<?= htmlspecialchars($id) ?>"><?= htmlspecialchars($teks) ?></option>
                                    <?php } ?>
                                </select>
                            <?php } else { ?>
                                <input type="<?= $k === 'password' ? 'password' : 'text' ?>"
                                       name="<?= htmlspecialchars($k) ?>"
                                       class="form-control"
                                       placeholder="<?= htmlspecialchars($k) ?>" required>
                            <?php } ?>
                        </div>
                    <?php } ?>
                    <div class="col-md-auto d-grid">
                        <button type="submit" class="btn btn-success">Tambah</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <?php foreach ($kolomTampil as $k) { ?>
                                    <th><?= htmlspecialchars($k) ?></th>
                                <?php } ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($baris = $hasil->fetch_assoc()) { ?>
                            <tr>
                                <?php foreach ($kolomTampil as $k) { ?>
                                    <td>
                                    <?php if ($k === 'status') { ?>
                                        <?= $baris[$k] == 1
                                            ? '<span class="badge bg-success">Aktif</span>'
                                            : '<span class="badge bg-secondary">Nonaktif</span>' ?>
                                    <?php } elseif (isset($opsi[$k])) { ?>
                                        <?= htmlspecialchars($opsi[$k][$baris[$k]] ?? (string)$baris[$k]) ?>
                                    <?php } else { ?>
                                        <?= htmlspecialchars((string)$baris[$k]) ?>
                                    <?php } ?>
                                    </td>
                                <?php } ?>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</body>
</html>