<?php
/**
 * Bootstrap untuk semua endpoint JSON di folder api/.
 * Setiap endpoint: hanya POST, wajib login dengan peran yang sesuai, wajib token CSRF.
 */
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function api_out($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
function api_ok($extra = []) { api_out(['ok' => true] + $extra); }
function api_fail($pesan, $code = 400) { api_out(['ok' => false, 'error' => $pesan], $code); }

function api_guard($role) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_fail('Metode tidak diizinkan.', 405);
    $u = refresh_session_user();
    if (!$u) api_fail('Sesi berakhir. Silakan masuk lagi.', 401);
    if ($u['role'] !== $role) api_fail('Anda tidak punya akses untuk aksi ini.', 403);
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf'] ?? '');
    if (!csrf_valid($token)) api_fail('Permintaan tidak valid. Muat ulang halaman lalu coba lagi.', 419);
    return $u;
}

/** Jalankan handler aksi; AksiError -> pesan ke pengguna, error lain dicatat & disamarkan. */
function api_run(callable $fn) {
    try {
        $hasil = $fn();
        api_ok(is_array($hasil) ? $hasil : []);
    } catch (AksiError $e) {
        api_fail($e->getMessage(), 422);
    } catch (Throwable $e) {
        error_log('[KostKu API] ' . $e->getMessage());
        api_fail('Terjadi kesalahan pada server. Coba lagi.', 500);
    }
}

function post($k, $d = '') { return $_POST[$k] ?? $d; }
