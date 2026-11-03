<?php
$host = 'localhost';
$user = 'pbd_user';
$pass = 'pbd123';
$db   = 'proyek_pbd';

$koneksi = new mysqli($host, $user, $pass, $db);
if ($koneksi->connect_error) {
    die('Koneksi gagal: ' . $koneksi->connect_error);
}