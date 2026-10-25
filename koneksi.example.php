<?php
$host = 'localhost';
$user = 'isi_user_mysql';
$pass = 'isi_password_mysql';
$db   = 'proyek_pbd';

$koneksi = new mysqli($host, $user, $pass, $db);
if ($koneksi->connect_error) {
    die('Koneksi gagal: ' . $koneksi->connect_error);
}