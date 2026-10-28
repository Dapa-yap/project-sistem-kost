<?php
/**
 * Koneksi database (PostgreSQL).
 * Bisa diubah lewat environment variable (DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS),
 * kalau tidak diatur dipakai nilai bawaan di bawah.
 */
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '5432';
$db   = getenv('DB_NAME') ?: 'db_pbl';
$user = getenv('DB_USER') ?: 'postgres';
$pass = getenv('DB_PASS') ?: 'postgres';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => true,   // parameter dikirim sebagai literal, aman untuk kolom enum
    ]);
} catch (PDOException $e) {
    error_log('Koneksi database gagal: ' . $e->getMessage());
    http_response_code(500);
    die('Koneksi database gagal. Periksa config/database.php dan pastikan PostgreSQL berjalan.');
}
