<?php
// Konfigurasi koneksi database
$servername = "localhost";
$username = "root"; // Sesuaikan jika ada username lain
$password = "";     // Kosongkan jika tidak ada password
$dbname = "db_penduduk_pgweb_acara8"; // Nama database

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Memeriksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>