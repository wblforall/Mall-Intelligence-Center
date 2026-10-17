<?php
/**
 * Server TIRUAN feed FlowStore untuk menguji driver http MIC (bukan untuk produksi).
 *
 *   FLOWSTORE_TIRUAN_TOKEN=rahasia FLOWSTORE_TIRUAN_BERKAS=/path/fixture.json \
 *   php -S 127.0.0.1:8099 scripts/flowstore-tiruan.php
 *
 * Aturan token SAMA dengan feed asli: token server kosong → 404 (feed mati);
 * header X-Feed-Token salah/tidak ada → 401.
 * FLOWSTORE_TIRUAN_FEED_LAMA=1 membuang field keterangan order (keperluan, spesifikasi, no_mr, keterangan_pr/po/btb)
 * dari jawaban, meniru FlowStore versi lama — untuk menguji toleransi MIC. Isi disajikan oleh FlowstoreFeed::sajikan().
 */
require __DIR__ . '/../app/Libraries/FlowstoreFeed.php';

use App\Libraries\FlowstoreFeed;

function jawab(int $kode, array $isi): void
{
    http_response_code($kode);
    header('Content-Type: application/json');
    echo json_encode($isi, JSON_UNESCAPED_UNICODE);
    exit;
}
function env2(string $k, string $d = ''): string { $v = getenv($k); return $v === false ? $d : $v; }
if (! function_exists('env')) { function env(string $k, $d = null) { return $d; } }   // FlowstoreFeed hanya butuh ini di konstruktor, tak dipakai di sini

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (! in_array($path, ['/api/feed/btb-item', '/api/feed/divisi'], true)) jawab(404, ['galat' => 'tidak ada']);

$tokenServer = env2('FLOWSTORE_TIRUAN_TOKEN');
if ($tokenServer === '') jawab(404, ['galat' => 'tidak ada']);
$kirim = $_SERVER['HTTP_X_FEED_TOKEN'] ?? '';
if (! hash_equals($tokenServer, $kirim)) jawab(401, ['galat' => 'token salah']);

$berkas = env2('FLOWSTORE_TIRUAN_BERKAS');
$j = is_file($berkas) ? json_decode((string) file_get_contents($berkas), true) : null;
if (! is_array($j)) jawab(500, ['galat' => 'fixture tidak terbaca']);

if (env2('FLOWSTORE_TIRUAN_KURSOR_RUSAK') !== '' && isset($_GET['setelah_id'])) jawab(400, ['galat' => 'FEED_KURSOR_TIDAK_DIKENAL']);
if (env2('FLOWSTORE_TIRUAN_LAMBAT') !== '') sleep((int) env2('FLOWSTORE_TIRUAN_LAMBAT'));
$r = FlowstoreFeed::sajikan($j, $path === '/api/feed/divisi' ? 'divisi' : 'btb-item', $_GET);
if (env2('FLOWSTORE_TIRUAN_FEED_LAMA') !== '' && isset($r['items'])) {
    $buang = array_flip(['keperluan', 'spesifikasi', 'no_mr', 'keterangan_pr', 'keterangan_po', 'keterangan_btb']);
    $r['items'] = array_map(fn ($i) => array_diff_key($i, $buang), $r['items']);
}
jawab((int) ($r['_status'] ?? 200), $r);
