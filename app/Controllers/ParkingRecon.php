<?php

namespace App\Controllers;

/**
 * Analisa terpisah: bandingkan data yang KITA REKAM (snapshot live) vs data FINAL SPI.
 * Hanya level TOTAL (capture tak punya per-jenis). Perbandingan muncul untuk hari yang
 * punya rekaman DAN sudah difinalkan SPI (±H-3). Halaman mandiri (mungkin sementara).
 */
class ParkingRecon extends BaseController
{
    public function index()
    {
        $canVeh = $this->canViewMenu('parking_vehicles');
        $canRev = $this->canViewMenu('parking_revenue');
        if (! $canVeh && ! $canRev) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $db = \Config\Database::connect();
        $has = fn($t) => $db->tableExists($t);

        // Capture: EOD snapshot per hari (income/tunai/nontunai/payments)
        $eod = [];
        if ($has('spi_live_snapshot')) {
            $rows = $db->query('SELECT s.tanggal, s.total_income, s.tunai, s.nontunai, s.payments_json '
                . 'FROM spi_live_snapshot s JOIN (SELECT tanggal, MAX(captured_at) mx FROM spi_live_snapshot GROUP BY tanggal) m '
                . 'ON m.tanggal = s.tanggal AND m.mx = s.captured_at')->getResultArray();
            foreach ($rows as $r) {
                $eod[$r['tanggal']] = [
                    'income'   => (int) $r['total_income'],
                    'tunai'    => (int) $r['tunai'],
                    'nontunai' => (int) $r['nontunai'],
                    'payments' => json_decode($r['payments_json'] ?: '[]', true) ?: [],
                ];
            }
        }
        // Capture: arus masuk/keluar per hari
        $flow = [];
        if ($has('spi_hourly_flow')) {
            foreach ($db->query('SELECT tanggal, SUM(masuk) masuk, SUM(keluar) keluar FROM spi_hourly_flow GROUP BY tanggal')->getResultArray() as $r) {
                $flow[$r['tanggal']] = ['masuk' => (int) $r['masuk'], 'keluar' => (int) $r['keluar']];
            }
        }
        // Final SPI
        $vehFinal = $incFinal = [];
        if ($has('spi_vehicle_daily')) {
            foreach ($db->table('spi_vehicle_daily')->select('tanggal,total')->get()->getResultArray() as $r) { $vehFinal[$r['tanggal']] = (int) $r['total']; }
        }
        if ($has('spi_income_daily')) {
            foreach ($db->table('spi_income_daily')->select('tanggal,total')->get()->getResultArray() as $r) { $incFinal[$r['tanggal']] = (int) $r['total']; }
        }

        // Gabung per hari (yang punya rekaman), terbaru dulu, maks 45
        $days = array_unique(array_merge(array_keys($eod), array_keys($flow)));
        rsort($days);
        $days = array_slice($days, 0, 45);
        $today = date('Y-m-d');
        $rows = [];
        foreach ($days as $t) {
            $rows[] = [
                'tanggal'    => $t,
                'partial'    => ($t === $today),
                'capMasuk'   => $flow[$t]['masuk'] ?? null,
                'finMasuk'   => $vehFinal[$t] ?? null,
                'capIncome'  => $eod[$t]['income'] ?? null,
                'finIncome'  => $incFinal[$t] ?? null,
                'capTunai'   => $eod[$t]['tunai'] ?? null,
                'capNontunai' => $eod[$t]['nontunai'] ?? null,
                'final'      => isset($vehFinal[$t]) || isset($incFinal[$t]),
            ];
        }

        // Perbandingan metode pembayaran untuk 1 hari (default: hari terbaru yg punya rekaman payment)
        $payDays = array_values(array_filter(array_keys($eod), fn($t) => ! empty($eod[$t]['payments'])));
        rsort($payDays);
        $payDate = $this->request->getGet('paydate') ?: ($payDays[0] ?? null);
        $payCompare = [];
        if ($canRev && $payDate) {
            $cap = [];
            foreach (($eod[$payDate]['payments'] ?? []) as $p) {
                $cap[$this->normKey($p['method'] ?? '')] = ['label' => $p['method'] ?? '', 'amt' => (int) ($p['amount'] ?? 0)];
            }
            $fin = [];
            if ($has('spi_payment_daily')) {
                foreach ($db->table('spi_payment_daily')->select('method, SUM(amount) total')->where('tanggal', $payDate)->groupBy('method')->get()->getResultArray() as $r) {
                    $fin[$this->normKey($r['method'])] = ['label' => $r['method'], 'amt' => (int) $r['total']];
                }
            }
            foreach (array_unique(array_merge(array_keys($cap), array_keys($fin))) as $k) {
                $payCompare[] = [
                    'method' => $cap[$k]['label'] ?? $fin[$k]['label'] ?? $k,
                    'cap'    => $cap[$k]['amt'] ?? null,
                    'fin'    => $fin[$k]['amt'] ?? null,
                ];
            }
            usort($payCompare, fn($a, $b) => (($b['cap'] ?? $b['fin'] ?? 0)) <=> (($a['cap'] ?? $a['fin'] ?? 0)));
        }

        // Perbandingan per JENIS untuk hari terpilih (capture vs final SPI)
        $typeCompare = [];
        if ($payDate) {
            $cap = [];
            if ($has('spi_capture_type_daily')) {
                foreach ($db->table('spi_capture_type_daily')->where('tanggal', $payDate)->get()->getResultArray() as $r) {
                    $cap[$r['jenis']] = ['masuk' => (int) $r['masuk'], 'income' => (int) $r['income']];
                }
            }
            $vfin = $has('spi_vehicle_daily') ? $db->table('spi_vehicle_daily')->where('tanggal', $payDate)->get()->getRowArray() : null;
            $ifin = $has('spi_income_daily')  ? $db->table('spi_income_daily')->where('tanggal', $payDate)->get()->getRowArray()  : null;
            foreach (['mobil', 'motor', 'box', 'truck', 'taxi', 'bus'] as $t) {
                if (! isset($cap[$t]) && ! $vfin && ! $ifin) { continue; }
                $typeCompare[] = [
                    'jenis'     => $t,
                    'capMasuk'  => $cap[$t]['masuk'] ?? null,
                    'finMasuk'  => $vfin ? (int) ($vfin[$t] ?? 0) : null,
                    'capIncome' => $cap[$t]['income'] ?? null,
                    'finIncome' => $ifin ? (int) ($ifin[$t] ?? 0) : null,
                ];
            }
        }

        return view('parking/recon', [
            'title'      => 'Rekaman vs SPI Final — Parkir',
            'typeCompare' => $typeCompare,
            'canVeh'     => $canVeh,
            'canRev'     => $canRev,
            'rows'       => $rows,
            'payDays'    => $payDays,
            'payDate'    => $payDate,
            'payCompare' => $payCompare,
            'latestFinal' => $vehFinal ? max(array_keys($vehFinal)) : null,
        ]);
    }

    /** Normalisasi nama metode utk pencocokan (capture vs SPI bisa beda ejaan). */
    private function normKey(string $s): string
    {
        $s = strtolower(preg_replace('/[^a-z0-9]/i', '', $s));
        $map = ['emoney' => 'emoney', 'bnitapcash' => 'tapcash', 'tapcash' => 'tapcash',
            'bribrizzi' => 'brizzi', 'brizzi' => 'brizzi', 'doomo' => 'doomo', 'flazz' => 'flazz', 'tunai' => 'tunai'];
        return $map[$s] ?? $s;
    }
}
