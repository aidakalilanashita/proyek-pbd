<?php
require 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama   = $_POST['nama_satuan'];
    $status = 1;
    $stmt = $koneksi->prepare("INSERT INTO v_satuan (nama_satuan, status) VALUES (?, ?)");
    $stmt->bind_param("si", $nama, $status);
    $stmt->execute();
    header("Location: satuan.php");
    exit;
}

$hasil = $koneksi->query("SELECT * FROM v_satuan");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Data Satuan</title>
</head>
<body>
    <h2>Data Satuan</h2>

    <form method="post">
        <input type="text" name="nama_satuan" placeholder="Nama satuan" required>
        <button type="submit">Tambah</button>
    </form>

    <br>
    <table border="1" cellpadding="6">
        <tr>
            <th>ID</th>
            <th>Nama Satuan</th>
            <th>Status</th>
        </tr>
        <?php while ($baris = $hasil->fetch_assoc()) { ?>
        <tr>
            <td><?= $baris['idsatuan'] ?></td>
            <td><?= htmlspecialchars($baris['nama_satuan']) ?></td>
            <td><?= $baris['status'] ?></td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>