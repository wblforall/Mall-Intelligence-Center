<?php

namespace App\Controllers;

use App\Models\CreativeItemModel;
use App\Models\CreativeRealisasiModel;
use App\Models\CreativeFileModel;
use App\Models\CreativeInsightModel;
use App\Models\CreativeReviewModel;
use App\Models\EventCreativeItemModel;
use App\Models\EventCreativeRealisasiModel;
use App\Models\EventCreativeInsightModel;
use App\Libraries\ActivityLog;
use App\Libraries\CreativeBiayaFs;
use App\Services\CreativeApprovalService;
use App\Services\CreativeTemaService;
use App\Services\CreativeTurunanService;
use App\Services\CreativeTitikService;
use App\Services\CreativeTurunanOtomatis;
use App\Models\CreativeTemaModel;

class CreativeCtrl extends BaseController
{
    private function realisasiDir(int $id): string
    {
        $dir = FCPATH . 'uploads/creative-standalone-realisasi/' . $id . '/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        return $dir;
    }

    private function fileDir(int $id): string
    {
        $dir = FCPATH . 'uploads/creative-standalone/' . $id . '/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        return $dir;
    }

    private function insightDir(int $id): string
    {
        $dir = FCPATH . 'uploads/creative-standalone-insight/' . $id . '/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        return $dir;
    }

    public function index()
    {
        if (!$this->canViewMenu('creative_main')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $user = $this->currentUser();

        $standaloneItems = (new CreativeItemModel())->getAll();
        foreach ($standaloneItems as &$item) { $item['_source'] = 's'; }
        unset($item);

        $eventItems = (new EventCreativeItemModel())->getAllWithEvents();
        foreach ($eventItems as &$item) { $item['_source'] = 'e'; }
        unset($item);

        $standaloneIds = array_column($standaloneItems, 'id');
        $eventItemIds  = array_column($eventItems, 'id');

        $files          = (new CreativeFileModel())->getGroupedByItems($standaloneIds);
        $reviews        = (new CreativeReviewModel())->getGroupedByItems(CreativeApprovalService::SCOPE_STANDALONE, $standaloneIds);
        $realisasi      = (new CreativeRealisasiModel())->getGroupedByItems($standaloneIds);
        $eventRealisasi = empty($eventItemIds) ? [] : (new EventCreativeRealisasiModel())->getGroupedByItems($eventItemIds);
        // Biaya dari FlowStore (BTB tertaut ke item): ditambahkan ke realisasi manual lama. Tanpa tautan, hasilnya identik dengan semula.
        $biayaFs       = new CreativeBiayaFs();
        $realisasi      = $biayaFs->gabung($realisasi, 'standalone', $standaloneIds);
        $eventRealisasi = $biayaFs->gabung($eventRealisasi, 'event', $eventItemIds);
        $insights       = (new CreativeInsightModel())->getGroupedByItems($standaloneIds);
        $eventInsights  = empty($eventItemIds) ? [] : (new EventCreativeInsightModel())->getGroupedByItems($eventItemIds);

        // Fase kartu: 'realisasi' bila sudah ada realisasi, selain itu 'review'
        foreach ($standaloneItems as &$item) {
            $item['_tab'] = (($realisasi[$item['id']]['total'] ?? 0) > 0) ? 'realisasi' : 'review';
        }
        unset($item);
        foreach ($eventItems as &$item) {
            $item['_tab'] = (($eventRealisasi[$item['id']]['total'] ?? 0) > 0) ? 'realisasi' : 'review';
        }
        unset($item);

        // Item "Rencana" (dari paket awal, belum dikerjakan) tampil di kartu Tema tetapi
        // TIDAK ikut KPI / hitungan tab: dihitung terpisah.
        $bukanRencana = fn($it) => empty($it['dari_paket']);
        $statS = array_values(array_filter($standaloneItems, $bukanRencana));
        $statE = array_values(array_filter($eventItems, $bukanRencana));
        $nRencana = (count($standaloneItems) - count($statS)) + (count($eventItems) - count($statE));

        // Pengelompokan per jenis (hanya untuk hitungan KPI/tab di tampilan)
        $byTipe = [];
        foreach (array_merge($statS, $statE) as $item) { $byTipe[$item['tipe']][] = $item; }

        $totalBudgetStandalone = (new CreativeItemModel())->getTotalBudget();
        $totalBudgetEvent      = array_sum(array_column($eventItems, 'budget'));
        $totalBudget           = $totalBudgetStandalone + $totalBudgetEvent;
        $totalRealisasi        = (empty($standaloneIds) ? 0 : (new CreativeRealisasiModel())->getTotalRealisasi($standaloneIds)) + $biayaFs->total('standalone', $standaloneIds);

        $tabCounts = ['review' => 0, 'realisasi' => 0];
        foreach ($byTipe as $tipeItems) {
            foreach ($tipeItems as $item) {
                $tabCounts[$item['_tab']] = ($tabCounts[$item['_tab']] ?? 0) + 1;
            }
        }

        // Tema sebagai induk: kelompok bulan -> kartu Tema -> item per jenis
        $tema = (new CreativeTemaService())->susun($standaloneItems, $eventItems, [
            'sReal' => $realisasi, 'eReal' => $eventRealisasi, 'sIns' => $insights, 'eIns' => $eventInsights,
        ]);

        // Turunan Design / Motion Graphic (standalone dan event) + katalog untuk daftar centang
        $desain = fn($it) => CreativeApprovalService::isDesain($it['tipe']);
        $turunanS = CreativeTurunanService::standalone()->untukItems(array_column(array_filter($standaloneItems, $desain), 'id'));
        $turunanE = [];
        $evIds = array_column(array_filter($eventItems, $desain), 'id');
        if ($evIds) {
            $grupEv = [];
            foreach (array_filter($eventItems, $desain) as $it) $grupEv[(int) $it['event_id']][] = (int) $it['id'];
            foreach ($grupEv as $eid => $ids) $turunanE += CreativeTurunanService::forEvent($eid)->untukItems($ids);
        }
        // Titik Media Cetak (standalone dan event) + Design induk per Tema untuk formulir
        $isCetak = fn($it) => $it['tipe'] === 'cetak';
        $titikS = CreativeTitikService::standalone()->untukItems(array_column(array_filter($standaloneItems, $isCetak), 'id'));
        $titikE = [];
        $grupTe = [];
        foreach (array_filter($eventItems, $isCetak) as $it) $grupTe[(int) $it['event_id']][] = (int) $it['id'];
        foreach ($grupTe as $eid => $ids) $titikE += CreativeTitikService::forEvent($eid)->untukItems($ids);
        $designMap = [];
        foreach (($tema['cards'] ?? []) as $ck => $c) {
            $ds = [];
            foreach (['e', 's'] as $src) {
                $list = array_filter($c['items'], fn($it) => $it['tipe'] === 'master_design' && $it['_source'] === $src);
                usort($list, fn($a, $b) => [(int) $a['urutan'], (int) $a['id']] <=> [(int) $b['urutan'], (int) $b['id']]);
                foreach ($list as $it) $ds[] = ['ref' => ($src === 's' ? 'standalone' : 'event') . ':' . $it['id'], 'nama' => $it['nama']];
            }
            if ($ds) $designMap[$ck] = $ds;
        }
        $canKatalog = $this->canApproveCreative();

        $canEdit = $this->canEditMenu('creative_main');

        return view('creative_main/index', [
            'user'           => $user,
            'byTipe'         => $byTipe,
            'standaloneItems'=> $statS,
            'eventItems'     => $statE,
            'nRencana'       => $nRencana,
            'turunanS'       => $turunanS,
            'turunanE'       => $turunanE,
            'titikS'         => $titikS,
            'titikE'         => $titikE,
            'designMap'      => $designMap,
            'jenisMaster'    => CreativeTitikService::jenisBerMaster(),
            'katalog'        => CreativeTurunanService::katalog(true),
            'cetakKatalog'   => CreativeTurunanService::cetakKatalog(),
            'canKatalog'     => $canKatalog,
            'files'          => $files,
            'reviews'        => $reviews,
            'realisasi'      => $realisasi,
            'eRealisasi'     => $eventRealisasi,
            'fsBuka'         => $this->urlAntreanFs(),
            'insights'       => $insights,
            'totalBudget'    => $totalBudget,
            'totalRealisasi' => $totalRealisasi,
            'canEdit'        => $canEdit,
            'canApprove'     => $this->canApproveCreative(),
            'canClose'       => $this->canApproveCreative(),
            'tabCounts'      => $tabCounts,
            'tema'           => $tema,
            'temaPilihan'    => $this->temaPilihan($tema['cards'] ?? []),
        ]);
    }


    /**
     * Tautan "Buka antrean FlowStore" (tab FlowStore Budget Departemen CCD) — hanya bila pengguna punya hak budget_dept dan dept CCD
     * terlihat olehnya (admin, atau dept sendiri). Selain itu '' : tim Creative tanpa hak budget hanya MELIHAT biaya tertaut.
     */
    private function urlAntreanFs(): string
    {
        $ccd = CreativeBiayaFs::deptCcdId();
        if (! $ccd || ! $this->canViewMenu('budget_dept')) return '';
        if (! $this->isAdmin() && (int) session()->get('dept_id') !== $ccd) return '';
        return base_url('budget-dept/flowstore?dept=' . $ccd);
    }

    /** Kolom Media Cetak (jenis, ukuran, periode pasang, Design induk); NULL untuk jenis lain. */
    private function cetakFields(array $post): array
    {
        return CreativeTitikService::dataCetak($post);
    }

    /** Kunci Tema ('t5' / 'e13' / '') sebuah item standalone. */
    private function temaKeyItem(?array $item): string
    {
        return $item ? CreativeTitikService::infoTema('standalone', $item)['key'] : '';
    }

    /** Sinkron turunan otomatis untuk satu atau dua Tema (mis. sebelum dan sesudah item dipindah). */
    private function sinkronTurunan(array $keys, string $alasan = ''): void
    {
        foreach (array_unique(array_filter($keys)) as $k) CreativeTurunanOtomatis::sinkron($k, CreativeTurunanOtomatis::SUMBER_CETAK, $alasan);
    }

    /** Pilihan Tema untuk formulir item: Tema Bulanan & Event yang belum ditutup. */
    private function temaPilihan(array $cards): array
    {
        $out = ['bulanan' => [], 'event' => []];
        foreach ($cards as $c) {
            if (! empty($c['closed'])) continue;
            $out[$c['kind'] === 'event' ? 'event' : 'bulanan'][] = [
                'val' => $c['key'], 'nama' => $c['nama'],
                'ket' => $c['kind'] === 'event' ? CreativeTemaService::bulanLabel((string) $c['bulan']) : CreativeTemaService::bulanLabel((string) $c['bulan']),
                'bulan' => $c['bulan'],
            ];
        }
        usort($out['bulanan'], fn($a, $b) => strcmp($b['bulan'], $a['bulan']));
        usort($out['event'],   fn($a, $b) => strcmp($b['bulan'], $a['bulan']));
        return $out;
    }

    /** Terjemahkan pilihan "t5" / "e13" / "" menjadi [tema_id, event_id] (atau null bila tak valid / sudah ditutup). */
    private function parseTema(?string $v, ?array $itemLama = null): ?array
    {
        $v = trim((string) $v);
        if ($v === '' || $v === 'none') return ['tema_id' => null, 'event_id' => null];
        if (! preg_match('/^([te])(\d+)$/', $v, $m)) return null;
        $id = (int) $m[2];
        if ($m[1] === 't') {
            $t = (new CreativeTemaModel())->find($id);
            if (! $t) return null;
            // memindah ke Tema yang sudah ditutup ditolak (kecuali tidak berubah)
            if ((int) $t['ditutup'] === 1 && (int) ($itemLama['tema_id'] ?? 0) !== $id) return null;
            return ['tema_id' => $id, 'event_id' => null];
        }
        $ev = db_connect()->table('events')->select('id')->where('id', $id)->get()->getRowArray();
        if (! $ev) return null;
        if (CreativeTemaService::eventDitutup($id) && (int) ($itemLama['event_id'] ?? 0) !== $id) return null;
        return ['tema_id' => null, 'event_id' => $id];
    }

    /** Penolakan standar bila Tema item ini sudah ditutup. */
    private function tolakBilaTemaTutup(int $id)
    {
        $item = (new CreativeItemModel())->find($id);
        if ($item && CreativeTemaService::itemTerkunci($item)) {
            return redirect()->to('/creative#item-' . $id . '-s')->with('error', CreativeTemaService::PESAN_TEMA_TUTUP);
        }
        return null;
    }

    public function monthly()
    {
        if (!$this->canViewMenu('creative_main')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $bulan = $this->request->getGet('bulan') ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) $bulan = date('Y-m');

        $data = $this->computeCreativeMonthly($bulan);

        // Nama Tema tiap item standalone (hanya tampilan; angka tidak berubah)
        $temaNama = [];
        $db = db_connect();
        $tn = []; foreach ($db->table('creative_tema')->select('id,nama')->get()->getResultArray() as $t) $tn['t' . $t['id']] = $t['nama'];
        foreach ($db->table('events')->select('id,name')->get()->getResultArray() as $e) $tn['e' . $e['id']] = $e['name'];
        foreach ($data['rows'] as $r) {
            $it = $r['item'];
            if (($it['_source'] ?? '') !== 's') continue;
            $k = ! empty($it['tema_id']) ? 't' . $it['tema_id'] : (! empty($it['event_id']) ? 'e' . $it['event_id'] : '');
            if ($k !== '' && isset($tn[$k])) $temaNama[(int) $it['id']] = $tn[$k];
        }

        return view('creative_main/monthly', ['user' => $this->currentUser(), 'temaNama' => $temaNama] + $data);
    }

    // Hitung seluruh data summary creative untuk satu bulan (dipakai layar & cetak)
    private function computeCreativeMonthly(string $bulan): array
    {
        [$year, $month] = explode('-', $bulan);
        $year  = (int)$year;
        $month = (int)$month;

        $prevBulan = date('Y-m', mktime(0, 0, 0, $month - 1, 1, $year));
        $nextBulan = date('Y-m', mktime(0, 0, 0, $month + 1, 1, $year));

        // Item "Rencana" (paket awal, belum dikerjakan) tidak ikut summary
        $standaloneItems = array_values(array_filter((new CreativeItemModel())->getAll(), fn($i) => empty($i['dari_paket'])));
        foreach ($standaloneItems as &$si) { $si['_source'] = 's'; }
        unset($si);

        $eventItems = array_values(array_filter((new EventCreativeItemModel())->getAllWithEvents(), fn($i) => empty($i['dari_paket'])));
        foreach ($eventItems as &$ei) { $ei['_source'] = 'e'; }
        unset($ei);

        $standaloneIds = array_column($standaloneItems, 'id');
        $eventItemIds  = array_column($eventItems, 'id');

        $sReal = empty($standaloneIds) ? [] : (new CreativeRealisasiModel())->getMonthlyGrouped($bulan, $standaloneIds);
        $eReal = empty($eventItemIds)  ? [] : (new EventCreativeRealisasiModel())->getMonthlyGrouped($bulan, $eventItemIds);
        $sIns  = empty($standaloneIds) ? [] : (new CreativeInsightModel())->getMonthlyGrouped($bulan, $standaloneIds);
        $eIns  = empty($eventItemIds)  ? [] : (new EventCreativeInsightModel())->getMonthlyGrouped($bulan, $eventItemIds);

        // Biaya FlowStore bulan ini ditambahkan ke realisasi manual (hanya bila ada BTB tertaut)
        $biayaFs = new CreativeBiayaFs();
        $sReal = $biayaFs->gabungBulanan($sReal, $bulan, 'standalone', $standaloneIds);
        $eReal = $biayaFs->gabungBulanan($eReal, $bulan, 'event', $eventItemIds);

        $allItems = array_merge($standaloneItems, $eventItems);

        $totalBudget    = array_sum(array_column($allItems, 'budget'));
        $totalRealisasi = array_sum(array_map(fn($g) => $g['total'] ?? 0, array_merge($sReal, $eReal)));
        $totalReach     = array_sum(array_map(fn($g) => $g['max_reach'] ?? 0, array_merge($sIns, $eIns)));
        $totalImpr      = array_sum(array_map(fn($g) => $g['max_impressions'] ?? 0, array_merge($sIns, $eIns)));
        $totalFollowers = array_sum(array_map(fn($g) => $g['total_followers_gained'] ?? 0, array_merge($sIns, $eIns)));

        // Engagement & efisiensi (bulan ini)
        $allIns = array_merge($sIns, $eIns);
        $totalViews      = array_sum(array_map(fn($g) => $g['max_views'] ?? 0, $allIns));
        $totalEngagement = array_sum(array_map(fn($g) =>
            ($g['max_likes'] ?? 0) + ($g['max_comments'] ?? 0) + ($g['max_shares'] ?? 0) + ($g['max_saves'] ?? 0), $allIns));
        $engagementRate  = $totalReach > 0 ? round($totalEngagement / $totalReach * 100, 1) : 0;
        $cpm             = $totalImpr  > 0 ? (int) round($totalRealisasi / $totalImpr * 1000) : 0; // biaya / 1.000 impresi
        $costPerReach    = $totalReach > 0 ? (int) round($totalRealisasi / $totalReach) : 0;

        // Serapan budget kumulatif (realisasi s/d bulan ini ÷ total budget)
        $realCumulative = (new CreativeRealisasiModel())->getTotalUpTo($bulan, $standaloneIds)
                        + (new EventCreativeRealisasiModel())->getTotalUpTo($bulan, $eventItemIds)
                        + $biayaFs->total('standalone', $standaloneIds, $bulan) + $biayaFs->total('event', $eventItemIds, $bulan);
        $serapanPct     = $totalBudget > 0 ? round($realCumulative / $totalBudget * 100, 1) : 0;

        // Delta vs bulan sebelumnya
        $prev = $this->creativeMonthlyTotals($prevBulan, $standaloneIds, $eventItemIds);

        // Tren 6 bulan terakhir
        $trendLabels = $trendReach = $trendImpr = $trendEng = $trendReal = $trendFoll = [];
        $shortBulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
        for ($i = 5; $i >= 0; $i--) {
            $mt = date('Y-m', mktime(0, 0, 0, $month - $i, 1, $year));
            $t  = $this->creativeMonthlyTotals($mt, $standaloneIds, $eventItemIds);
            $dt = \DateTime::createFromFormat('Y-m', $mt);
            $trendLabels[] = $shortBulan[(int)$dt->format('n') - 1] . ' ' . $dt->format('y');
            $trendReach[]  = $t['reach'];
            $trendImpr[]   = $t['impr'];
            $trendEng[]    = $t['eng'];
            $trendReal[]   = $t['real'];
            $trendFoll[]   = $t['foll'];
        }

        $statusCounts = [];
        foreach ($allItems as $item) {
            $s = $item['status'] ?? 'draft';
            $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
        }

        $activeStSet = array_flip(array_unique(array_merge(array_keys($sReal), array_keys($sIns))));
        $activeEvSet = array_flip(array_unique(array_merge(array_keys($eReal), array_keys($eIns))));
        $activeCount = count($activeStSet) + count($activeEvSet);

        $rows = [];
        foreach ($allItems as $item) {
            $iid  = (int)$item['id'];
            $isSt = $item['_source'] === 's';

            $realMonth   = $isSt ? ($sReal[$iid] ?? null) : ($eReal[$iid] ?? null);
            $insMonth    = $isSt ? ($sIns[$iid]  ?? null) : ($eIns[$iid]  ?? null);
            $hasActivity = $isSt ? isset($activeStSet[$iid]) : isset($activeEvSet[$iid]);

            // Item standalone: hanya tampil di bulan miliknya (dari tanggal item, fallback tanggal_take)
            // atau bila ada aktivitas (realisasi/insight) di bulan ini.
            if ($isSt) {
                $itemMonth = substr(($item['tanggal'] ?? '') ?: ($item['tanggal_take'] ?? ''), 0, 7);
                if (! $hasActivity && $itemMonth !== $bulan) continue;
            }

            $rows[] = compact('item', 'realMonth', 'insMonth', 'hasActivity');
        }

        usort($rows, fn($a, $b) =>
            $b['hasActivity'] <=> $a['hasActivity'] ?: strcmp($a['item']['tipe'], $b['item']['tipe'])
        );

        $chartItems      = [];
        $chartBudget     = [];
        $chartRealMonth  = [];
        foreach ($rows as $r) {
            $budget  = (int)$r['item']['budget'];
            $realTot = (int)($r['realMonth']['total'] ?? 0);
            if ($budget > 0 || $realTot > 0) {
                $chartItems[]     = mb_strimwidth($r['item']['nama'], 0, 30, '…');
                $chartBudget[]    = $budget;
                $chartRealMonth[] = $realTot;
            }
        }

        $insightLabels = [];
        $insightReach  = [];
        $insightImpr   = [];
        foreach ($rows as $r) {
            if ($r['insMonth'] !== null) {
                $insightLabels[] = mb_strimwidth($r['item']['nama'], 0, 30, '…');
                $insightReach[]  = (int)($r['insMonth']['max_reach']       ?? 0);
                $insightImpr[]   = (int)($r['insMonth']['max_impressions'] ?? 0);
            }
        }

        // Konten terbaik bulan ini (by reach)
        $topItemName = ''; $topItemReach = 0; $topItemEng = 0;
        foreach ($rows as $r) {
            if ($r['insMonth'] === null) continue;
            $rr = (int)($r['insMonth']['max_reach'] ?? 0);
            if ($rr > $topItemReach) {
                $topItemReach = $rr;
                $topItemName  = $r['item']['nama'];
                $topItemEng   = (int)(($r['insMonth']['max_likes'] ?? 0) + ($r['insMonth']['max_comments'] ?? 0)
                                    + ($r['insMonth']['max_shares'] ?? 0) + ($r['insMonth']['max_saves'] ?? 0));
            }
        }

        // Analisa otomatis (berbasis aturan; bisa di-upgrade ke AI nanti)
        $analysis = $this->buildCreativeAnalysis([
            'bulan'           => $bulan,
            'totalItems'      => count($rows),
            'activeCount'     => $activeCount,
            'totalBudget'     => $totalBudget,
            'realCumulative'  => $realCumulative,
            'serapanPct'      => $serapanPct,
            'totalRealisasi'  => $totalRealisasi,
            'realPrev'        => $prev['real'],
            'totalReach'      => $totalReach,
            'reachPrev'       => $prev['reach'],
            'totalImpr'       => $totalImpr,
            'imprPrev'        => $prev['impr'],
            'totalEngagement' => $totalEngagement,
            'engPrev'         => $prev['eng'],
            'engagementRate'  => $engagementRate,
            'totalFollowers'  => $totalFollowers,
            'follPrev'        => $prev['foll'],
            'cpm'             => $cpm,
            'costPerReach'    => $costPerReach,
            'topItemName'     => $topItemName,
            'topItemReach'    => $topItemReach,
            'topItemEng'      => $topItemEng,
        ]);

        return [
            'bulan'           => $bulan,
            'year'            => $year,
            'month'           => $month,
            'prevBulan'       => $prevBulan,
            'nextBulan'       => $nextBulan,
            'rows'            => $rows,
            'activeCount'     => $activeCount,
            'totalBudget'     => $totalBudget,
            'totalRealisasi'  => $totalRealisasi,
            'totalReach'      => $totalReach,
            'totalImpressions'=> $totalImpr,
            'totalFollowers'  => $totalFollowers,
            'totalViews'      => $totalViews,
            'totalEngagement' => $totalEngagement,
            'engagementRate'  => $engagementRate,
            'cpm'             => $cpm,
            'costPerReach'    => $costPerReach,
            'realCumulative'  => $realCumulative,
            'serapanPct'      => $serapanPct,
            'prev'            => $prev,
            'trendLabels'     => $trendLabels,
            'trendReach'      => $trendReach,
            'trendImpr'       => $trendImpr,
            'trendEng'        => $trendEng,
            'trendReal'       => $trendReal,
            'trendFoll'       => $trendFoll,
            'topItemName'     => $topItemName,
            'topItemReach'    => $topItemReach,
            'topItemEng'      => $topItemEng,
            'statusCounts'    => $statusCounts,
            'chartItems'      => $chartItems,
            'chartBudget'     => $chartBudget,
            'chartRealMonth'  => $chartRealMonth,
            'insightLabels'   => $insightLabels,
            'insightReach'    => $insightReach,
            'insightImpr'     => $insightImpr,
            'analysis'        => $analysis,
        ];
    }

    // Generator analisa otomatis (rule-based) dari metrik bulanan
    private function buildCreativeAnalysis(array $m): array
    {
        $a   = [];
        $fmt = fn($n) => number_format((int)$n);
        $rp  = fn($n) => 'Rp ' . number_format((int)$n, 0, ',', '.');
        $pct = fn($cur, $prev) => $prev > 0 ? (int) round(($cur - $prev) / $prev * 100) : null;
        $arah = fn($d) => $d > 0 ? "naik {$d}%" : ($d < 0 ? "turun " . abs($d) . "%" : "stabil");

        if ($m['activeCount'] <= 0) {
            return ["Belum ada aktivitas (realisasi/insight) creative yang tercatat bulan ini."];
        }
        $a[] = "{$m['activeCount']} dari {$m['totalItems']} item creative aktif bulan ini.";

        if ($m['totalReach'] > 0) {
            $d = $pct($m['totalReach'], $m['reachPrev']);
            $a[] = $d === null
                ? "Total reach " . $fmt($m['totalReach']) . " (belum ada pembanding bulan lalu)."
                : "Total reach " . $fmt($m['totalReach']) . " — " . $arah($d) . " vs bulan lalu.";
        }

        if ($m['totalEngagement'] > 0) {
            $r = $m['engagementRate'];
            $nilai = $r >= 3 ? "tergolong sangat baik" : ($r >= 1 ? "tergolong sehat" : "tergolong rendah");
            $a[] = "Engagement " . $fmt($m['totalEngagement']) . " (rate {$r}% dari reach), {$nilai}.";
        }

        if ($m['totalBudget'] > 0) {
            $s = $m['serapanPct'];
            $cat = $s > 100 ? "melebihi budget — perlu perhatian" : ($s >= 80 ? "mendekati batas budget" : "masih dalam batas aman");
            $a[] = "Realisasi bulan ini " . $rp($m['totalRealisasi']) . "; serapan budget kumulatif {$s}% ({$cat}).";
        } elseif ($m['totalRealisasi'] > 0) {
            $a[] = "Realisasi bulan ini " . $rp($m['totalRealisasi']) . " (budget belum di-set).";
        }

        if ($m['cpm'] > 0) {
            $a[] = "Efisiensi: " . $rp($m['cpm']) . " per 1.000 impresi"
                 . ($m['costPerReach'] > 0 ? " (" . $rp($m['costPerReach']) . " per reach)." : ".");
        }

        if ($m['totalFollowers'] > 0) {
            $d = $pct($m['totalFollowers'], $m['follPrev']);
            $a[] = "Pertumbuhan follower +" . $fmt($m['totalFollowers']) . ($d === null ? "." : " (" . $arah($d) . " vs bulan lalu).");
        }

        if ($m['topItemReach'] > 0) {
            $a[] = "Konten terbaik: \"{$m['topItemName']}\" — " . $fmt($m['topItemReach']) . " reach"
                 . ($m['topItemEng'] > 0 ? " & " . $fmt($m['topItemEng']) . " engagement." : ".");
        }

        // Rekomendasi
        $rec = [];
        if ($m['totalEngagement'] > 0 && $m['engagementRate'] < 1) {
            $rec[] = "tingkatkan konten interaktif (CTA, pertanyaan, video) untuk mendongkrak engagement";
        }
        if ($m['totalBudget'] > 0 && $m['serapanPct'] < 50) {
            $rec[] = "percepat realisasi agar serapan budget tidak menumpuk di akhir periode";
        }
        if (($d = $pct($m['totalReach'], $m['reachPrev'])) !== null && $d < 0) {
            $rec[] = "evaluasi materi karena reach menurun dibanding bulan lalu";
        }
        if ($rec) $a[] = "Rekomendasi: " . implode('; ', $rec) . ".";

        return $a;
    }

    // Total agregat creative untuk satu bulan (untuk tren & delta)
    private function creativeMonthlyTotals(string $bulan, array $sIds, array $eIds): array
    {
        $sReal = $sIds ? (new CreativeRealisasiModel())->getMonthlyGrouped($bulan, $sIds) : [];
        $eReal = $eIds ? (new EventCreativeRealisasiModel())->getMonthlyGrouped($bulan, $eIds) : [];
        $biayaFs = new CreativeBiayaFs();
        $sReal = $biayaFs->gabungBulanan($sReal, $bulan, 'standalone', $sIds);
        $eReal = $biayaFs->gabungBulanan($eReal, $bulan, 'event', $eIds);
        $sIns  = $sIds ? (new CreativeInsightModel())->getMonthlyGrouped($bulan, $sIds) : [];
        $eIns  = $eIds ? (new EventCreativeInsightModel())->getMonthlyGrouped($bulan, $eIds) : [];
        $ins   = array_merge($sIns, $eIns);
        return [
            'real'  => (int) array_sum(array_map(fn($g) => $g['total'] ?? 0, array_merge($sReal, $eReal))),
            'reach' => (int) array_sum(array_map(fn($g) => $g['max_reach'] ?? 0, $ins)),
            'impr'  => (int) array_sum(array_map(fn($g) => $g['max_impressions'] ?? 0, $ins)),
            'views' => (int) array_sum(array_map(fn($g) => $g['max_views'] ?? 0, $ins)),
            'foll'  => (int) array_sum(array_map(fn($g) => $g['total_followers_gained'] ?? 0, $ins)),
            'eng'   => (int) array_sum(array_map(fn($g) =>
                ($g['max_likes'] ?? 0) + ($g['max_comments'] ?? 0) + ($g['max_shares'] ?? 0) + ($g['max_saves'] ?? 0), $ins)),
        ];
    }

    public function printMonthly()
    {
        if (!$this->canViewMenu('creative_main')) {
            return redirect()->to('/')->with('error', 'Akses ditolak.');
        }

        $bulan = $this->request->getGet('bulan') ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) $bulan = date('Y-m');

        return view('creative_main/print_monthly', [
            'printedBy' => $this->currentUser()['name'] ?? '',
            'printedAt' => date('d M Y H:i'),
        ] + $this->computeCreativeMonthly($bulan));
    }

    public function store()
    {
        if (!$this->canEditMenu('creative_main')) {
            return redirect()->to('/creative')->with('error', 'Akses ditolak.');
        }

        $post      = $this->request->getPost();
        if (! in_array($post['tipe'] ?? '', CreativeTemaService::TIPE_URUT, true)) {
            return redirect()->to('/creative')->with('error', 'Jenis item tidak dikenal.');
        }
        $isDigital = ($post['tipe'] === 'digital');

        $tm = $this->parseTema($post['tema'] ?? '');
        if ($tm === null) {
            return redirect()->to('/creative')->with('error', 'Tema yang dipilih tidak valid atau sudah ditutup.');
        }
        if ($err = CreativeTitikService::validasiCetak($post)) {
            return redirect()->to('/creative')->with('error', $err);
        }

        $newId = (new CreativeItemModel())->insert([
            'tipe'         => $post['tipe'],
            'nama'         => $post['nama'],
            'platform'     => $isDigital ? ($post['platform'] ?? null) : null,
            'tanggal_take' => $isDigital ? ($post['tanggal_take'] ?: null) : null,
            'jam_take'     => $isDigital ? ($post['jam_take'] ?: null) : null,
            'pic'          => $isDigital ? ($post['pic'] ?: null) : null,
            'deskripsi'    => $post['deskripsi'] ?? null,
            'tanggal'      => ($post['tanggal'] ?? '') ?: null,
            'budget'       => (int)str_replace([',', '.', ' '], '', $post['budget'] ?? 0),
            'is_closed'    => !empty($post['is_closed']) ? 1 : 0,
            'closed_at'    => !empty($post['is_closed']) ? (($post['closed_at'] ?? '') ?: date('Y-m-d')) : null,
            'catatan'      => $post['catatan'] ?? null,
            'urutan'       => (int)($post['urutan'] ?? 0),
            'target_reach'       => (($post['target_reach'] ?? '') !== '') ? max(0, (int) $post['target_reach']) : null,
            'target_impressions' => (($post['target_impressions'] ?? '') !== '') ? max(0, (int) $post['target_impressions']) : null,
            'deadline'           => ($post['deadline'] ?? '') ?: null,
            'created_by'   => $this->currentUser()['id'],
        ] + $this->cetakFields($post) + $tm);

        ActivityLog::write('create', 'creative_standalone', (string)$newId, $post['nama'], ['tipe' => $post['tipe']] + $tm);
        if ($post['tipe'] === 'cetak') $this->sinkronTurunan([$this->temaKeyItem((new CreativeItemModel())->find($newId))]);

        return redirect()->to('/creative#item-' . $newId . '-s')->with('success', 'Item berhasil ditambahkan.');
    }

    public function update(int $id)
    {
        if (!$this->canEditMenu('creative_main')) {
            return redirect()->to('/creative')->with('error', 'Akses ditolak.');
        }

        if ($r = $this->tolakBilaTemaTutup($id)) return $r;

        $post           = $this->request->getPost();
        $isDigital      = ($post['tipe'] === 'digital');
        $creativeModel  = new CreativeItemModel();
        $existing       = $creativeModel->find($id);

        if (CreativeApprovalService::isLocked($existing)) {
            return redirect()->to('/creative#item-' . $id . '-s')
                ->with('error', CreativeApprovalService::PESAN_TERKUNCI);
        }

        $tm = array_key_exists('tema', $post) ? $this->parseTema($post['tema'], $existing) : [];
        if ($tm === null) {
            return redirect()->to('/creative#item-' . $id . '-s')->with('error', 'Tema yang dipilih tidak valid atau sudah ditutup.');
        }

        if ($existing && $existing['tipe'] === 'cetak') $post['tipe'] = 'cetak';
        if ($err = CreativeTitikService::validasiCetak($post)) {
            return redirect()->to('/creative#item-' . $id . '-s')->with('error', $err);
        }
        $cetakBaru = $this->cetakFields($post);
        if ($existing && $existing['tipe'] === 'cetak' && ($err = CreativeTitikService::standalone()->selaraskan($existing, $cetakBaru))) {
            return redirect()->to('/creative#item-' . $id . '-s')->with('error', $err);
        }

        ActivityLog::captureBefore($existing);
        $creativeData = $tm + [
            'tipe'         => $post['tipe'],
            'nama'         => $post['nama'],
            'platform'     => $isDigital ? ($post['platform'] ?? null) : null,
            'tanggal_take' => $isDigital ? ($post['tanggal_take'] ?: null) : null,
            'jam_take'     => $isDigital ? ($post['jam_take'] ?: null) : null,
            'pic'          => $isDigital ? ($post['pic'] ?: null) : null,
            'deskripsi'    => $post['deskripsi'] ?? null,
            'tanggal'      => ($post['tanggal'] ?? '') ?: null,
            'budget'       => (int)str_replace([',', '.', ' '], '', $post['budget'] ?? 0),
            'is_closed'    => !empty($post['is_closed']) ? 1 : 0,
            'closed_at'    => !empty($post['is_closed']) ? (($post['closed_at'] ?? '') ?: date('Y-m-d')) : null,
            'catatan'      => $post['catatan'] ?? null,
            'urutan'       => (int)($post['urutan'] ?? 0),
            'target_reach'       => (($post['target_reach'] ?? '') !== '') ? max(0, (int) $post['target_reach']) : null,
            'target_impressions' => (($post['target_impressions'] ?? '') !== '') ? max(0, (int) $post['target_impressions']) : null,
            'deadline'           => ($post['deadline'] ?? '') ?: null,
        ] + $this->cetakFields($post);
        $creativeModel->update($id, $creativeData);
        ActivityLog::captureAfter($creativeData);

        ActivityLog::write('update', 'creative_standalone', (string)$id, $post['nama'], []);
        if ($existing && $existing['tipe'] === 'cetak') {
            CreativeTitikService::standalone()->segarkan($id);
            $berubah = ($existing['jenis_media'] ?? '') !== ($cetakBaru['jenis_media'] ?? '') || ($existing['ukuran'] ?? '') !== ($cetakBaru['ukuran'] ?? '');
            $this->sinkronTurunan([$this->temaKeyItem($existing), $this->temaKeyItem($creativeModel->find($id))], $berubah ? 'ukuran atau jenis berubah' : '');
        }

        return redirect()->to('/creative#item-' . $id . '-s')->with('success', 'Item berhasil diperbarui.');
    }

    /** Memindahkan item ke Tema lain (hanya penautan; boleh juga untuk materi yang sudah disetujui). */
    public function pindah(int $id)
    {
        if (!$this->canEditMenu('creative_main')) {
            return redirect()->to('/creative')->with('error', 'Akses ditolak.');
        }
        if ($r = $this->tolakBilaTemaTutup($id)) return $r;

        $model = new CreativeItemModel();
        $item  = $model->find($id);
        if (! $item) return redirect()->to('/creative')->with('error', 'Item tidak ditemukan.');

        $tm = $this->parseTema($this->request->getPost('tema'), $item);
        if ($tm === null) {
            return redirect()->to('/creative#item-' . $id . '-s')->with('error', 'Tema yang dipilih tidak valid atau sudah ditutup.');
        }
        $model->update($id, $tm);
        ActivityLog::write('update', 'creative_standalone', (string) $id, $item['nama'], ['aksi' => 'pindah_tema'] + $tm);
        if ($item['tipe'] === 'cetak') {
            CreativeTitikService::standalone()->segarkan($id);
            $this->sinkronTurunan([$this->temaKeyItem($item), $this->temaKeyItem($model->find($id))], 'item dipindah ke Tema lain');
        }

        return redirect()->to('/creative#item-' . $id . '-s')->with('success', 'Item dipindahkan.');
    }

    public function delete(int $id)
    {
        if (!$this->canEditMenu('creative_main')) {
            return redirect()->to('/creative')->with('error', 'Akses ditolak.');
        }

        if ($r = $this->tolakBilaTemaTutup($id)) return $r;

        $item         = (new CreativeItemModel())->find($id);

        if (CreativeApprovalService::isLocked($item)) {
            return redirect()->to('/creative#item-' . $id . '-s')
                ->with('error', CreativeApprovalService::PESAN_TERKUNCI);
        }

        if (CreativeTurunanService::adaDisetujui(CreativeTurunanService::SCOPE_STANDALONE, $id) > 0) {
            return redirect()->to('/creative#item-' . $id . '-s~turunan')
                ->with('error', 'Item punya turunan yang sudah disetujui. Buka kembali atau hapus turunannya dulu.');
        }

        $temaKeyLama = $this->temaKeyItem($item);
        $lepas = ($item && $item['tipe'] === 'cetak') ? CreativeTitikService::standalone()->lepasUntukItem($id) : null;
        $realisasiRows= (new CreativeRealisasiModel())->where('creative_item_id', $id)->findAll();
        $fileRows     = (new CreativeFileModel())->where('creative_item_id', $id)->findAll();
        $insightRows  = (new CreativeInsightModel())->where('creative_item_id', $id)->findAll();

        $db = db_connect();
        $db->transStart();
        (new CreativeInsightModel())->where('creative_item_id', $id)->delete();
        (new CreativeFileModel())->where('creative_item_id', $id)->delete();
        (new CreativeRealisasiModel())->where('creative_item_id', $id)->delete();
        (new CreativeItemModel())->delete($id);
        $db->transComplete();

        if (!$db->transStatus()) {
            return redirect()->to('/creative')->with('error', 'Gagal menghapus item. Silakan coba lagi.');
        }

        CreativeTurunanService::hapusUntukInduk(CreativeTurunanService::SCOPE_STANDALONE, [$id]);

        // Unlink physical files after successful transaction
        foreach ($realisasiRows as $r) {
            $dir = FCPATH . 'uploads/creative-standalone-realisasi/' . $id . '/';
            foreach (['file_name', 'serah_terima_file_name', 'bukti_terpasang_file_name'] as $col) {
                if ($r[$col] && file_exists($dir . $r[$col])) unlink($dir . $r[$col]);
            }
        }
        foreach ($fileRows as $f) {
            $path = FCPATH . 'uploads/creative-standalone/' . $id . '/' . $f['file_name'];
            if (file_exists($path)) unlink($path);
        }
        foreach ($insightRows as $ins) {
            if ($ins['file_name']) {
                $path = FCPATH . 'uploads/creative-standalone-insight/' . $id . '/' . $ins['file_name'];
                if (file_exists($path)) unlink($path);
            }
        }

        ActivityLog::write('delete', 'creative_standalone', (string)$id, $item['nama'] ?? '', []);
        if ($lepas !== null) $this->sinkronTurunan([$temaKeyLama], 'item dihapus');

        $msg = 'Item berhasil dihapus.';
        if ($lepas && ($lepas['dilepas'] + $lepas['riwayat']) > 0) {
            $msg .= ' ' . $lepas['dilepas'] . ' pemesanan titik di Media Promo dilepas' . ($lepas['riwayat'] ? ', ' . $lepas['riwayat'] . ' yang sudah selesai tetap sebagai riwayat' : '') . '.';
        }
        return redirect()->to('/creative')->with('success', $msg);
    }

    public function uploadFile(int $id)
    {
        if (!$this->canEditMenu('creative_main')) {
            return redirect()->to('/creative')->with('error', 'Akses ditolak.');
        }

        if ($r = $this->tolakBilaTemaTutup($id)) return $r;

        $item = (new CreativeItemModel())->find($id);
        if (CreativeApprovalService::isLocked($item)) {
            return redirect()->to('/creative#item-' . $id . '-s')
                ->with('error', CreativeApprovalService::PESAN_TERKUNCI);
        }

        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return redirect()->to('/creative#item-' . $id . '-s')->with('error', 'File tidak valid.');
        }

        if ($err = $this->validateUpload($file, array_merge(self::MIME_IMAGE, self::MIME_DOC), 20)) {
            return redirect()->to('/creative#item-' . $id . '-s')->with('error', $err);
        }
        $name = 'creative_s_' . $id . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $this->safeExt($file);
        $dir  = $this->fileDir($id);

        // Satu file = satu opsi desain, kecuali ditandai sebagai file pendukung.
        $meta = CreativeApprovalService::standalone()
            ->uploadMeta($id, ! $this->request->getPost('is_pendukung'));

        // Pindahkan file dulu, baru catat ke DB. Urutan sebaliknya meninggalkan
        // baris yatim yang menunjuk file tak ada bila direktori tidak writable.
        try {
            $file->move($dir, $name);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal memindahkan file creative: ' . $e->getMessage());
            return redirect()->to('/creative#item-' . $id . '-s')
                ->with('error', 'Gagal menyimpan file ke server. Periksa izin folder upload.');
        }

        $fileId = (new CreativeFileModel())->insert([
            'creative_item_id' => $id,
            'file_name'        => $name,
            'original_name'    => $file->getClientName(),
            'uploaded_by'      => $this->currentUser()['id'],
        ] + $meta);
        if (! $fileId) {
            if (is_file($dir . '/' . $name)) unlink($dir . '/' . $name);
            return redirect()->to('/creative#item-' . $id . '-s')->with('error', 'Gagal menyimpan file.');
        }
        \App\Libraries\ImageCompressor::compress($dir . '/' . $name);

        CreativeTemaService::tandaiDikerjakan('standalone', $id);
        ActivityLog::write('upload', 'creative_standalone', (string)$id, $name, [
            'opsi' => $meta['opsi_label'], 'versi' => $meta['versi'],
        ]);

        return redirect()->to('/creative#item-' . $id . '-s')->with(
            'success',
            $meta['is_opsi']
                ? 'Opsi ' . $meta['opsi_label'] . ' berhasil diupload.'
                : 'File pendukung berhasil diupload.'
        );
    }

    public function deleteFile(int $id, int $fileId)
    {
        if (!$this->canEditMenu('creative_main')) {
            return redirect()->to('/creative')->with('error', 'Akses ditolak.');
        }

        if ($r = $this->tolakBilaTemaTutup($id)) return $r;

        $item      = (new CreativeItemModel())->find($id);
        $fileModel = new CreativeFileModel();
        $row       = $fileModel->find($fileId);
        if ($row) {
            if ((int) $row['creative_item_id'] !== $id) {
                return redirect()->to('/creative#item-' . $id . '-s')->with('error', 'File tidak cocok dengan item.');
            }
            if (CreativeApprovalService::fileTerkunci($item, $row)) {
                return redirect()->to('/creative#item-' . $id . '-s')
                    ->with('error', CreativeApprovalService::pesanOpsiTerkunci($item));
            }
            $path = FCPATH . 'uploads/creative-standalone/' . $id . '/' . $row['file_name'];
            $fileModel->delete($fileId);
            if (file_exists($path)) unlink($path);
        }

        ActivityLog::write('delete_file', 'creative_standalone', (string)$fileId, '', []);

        return redirect()->to('/creative#item-' . $id . '-s')->with('success', 'File berhasil dihapus.');
    }

    // ─────────────────────────────────────────────────────────────
    // Alur persetujuan materi (lihat CreativeApprovalService)
    // ─────────────────────────────────────────────────────────────

    /** Desainer mengirim materi untuk ditinjau. */
    public function ajukan(int $id)
    {
        if (! $this->canEditMenu('creative_main')) {
            return redirect()->to('/creative')->with('error', 'Akses ditolak.');
        }

        return $this->hasilApproval(
            $id,
            CreativeApprovalService::standalone()->ajukan($id, $this->currentUser())
        );
    }

    /** Peninjau menyetujui sekaligus menetapkan opsi yang dipakai. */
    public function setujui(int $id)
    {
        if (! $this->canApproveCreative()) {
            return redirect()->to('/creative')->with('error', 'Anda tidak punya hak menyetujui materi creative.');
        }

        $fileIds = (array) ($this->request->getPost('opsi') ?? []);

        return $this->hasilApproval(
            $id,
            CreativeApprovalService::standalone()->setujui(
                $id,
                $fileIds,
                $this->currentUser(),
                $this->request->getPost('catatan')
            )
        );
    }

    /** Peninjau meminta revisi — alasan wajib. */
    public function revisi(int $id)
    {
        if (! $this->canApproveCreative()) {
            return redirect()->to('/creative')->with('error', 'Anda tidak punya hak menyetujui materi creative.');
        }

        $basis = $this->request->getPost('basis_file_id');

        return $this->hasilApproval(
            $id,
            CreativeApprovalService::standalone()->revisi(
                $id,
                (string) ($this->request->getPost('catatan') ?? ''),
                $basis !== null && $basis !== '' ? (int) $basis : null,
                $this->currentUser()
            )
        );
    }

    /** Membuka kembali materi yang sudah disetujui agar bisa diubah. */
    public function buka(int $id)
    {
        if (! $this->canEditMenu('creative_main') && ! $this->canApproveCreative()) {
            return redirect()->to('/creative')->with('error', 'Akses ditolak.');
        }

        return $this->hasilApproval(
            $id,
            CreativeApprovalService::standalone()->buka(
                $id,
                (string) ($this->request->getPost('catatan') ?? ''),
                $this->currentUser()
            )
        );
    }

    private function hasilApproval(int $id, array $hasil)
    {
        return redirect()->to('/creative#item-' . $id . '-s')
            ->with($hasil['ok'] ? 'success' : 'error', $hasil['message']);
    }

    public function storeRealisasi(int $id)
    {
        if (!$this->canEditMenu('creative_main')) {
            return redirect()->to('/creative')->with('error', 'Akses ditolak.');
        }

        if ($r = $this->tolakBilaTemaTutup($id)) return $r;

        $post        = $this->request->getPost();
        $dir         = $this->realisasiDir($id);
        $pendingMoves = [];

        $fileName = null; $origName = null;
        $file = $this->request->getFile('bukti');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            if ($err = $this->validateUpload($file, self::MIME_DOC, 15)) {
                return redirect()->back()->with('error', $err);
            }
            $fileName = 'real_s_' . $id . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $this->safeExt($file);
            $origName = $file->getClientName();
            $pendingMoves[] = [$file, $dir, $fileName];
        }

        $stFileName = null; $stOrigName = null;
        $stFile = $this->request->getFile('serah_terima');
        if ($stFile && $stFile->isValid() && !$stFile->hasMoved()) {
            if ($err = $this->validateUpload($stFile, self::MIME_DOC, 15)) {
                return redirect()->back()->with('error', $err);
            }
            $stFileName = 'st_s_' . $id . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $this->safeExt($stFile);
            $stOrigName = $stFile->getClientName();
            $pendingMoves[] = [$stFile, $dir, $stFileName];
        }

        $btFileName = null; $btOrigName = null;
        $btFile = $this->request->getFile('bukti_terpasang');
        if ($btFile && $btFile->isValid() && !$btFile->hasMoved()) {
            if ($err = $this->validateUpload($btFile, self::MIME_DOC, 15)) {
                return redirect()->back()->with('error', $err);
            }
            $btFileName = 'bt_s_' . $id . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $this->safeExt($btFile);
            $btOrigName = $btFile->getClientName();
            $pendingMoves[] = [$btFile, $dir, $btFileName];
        }

        $rid = (new CreativeRealisasiModel())->insert([
            'creative_item_id'              => $id,
            'tanggal'                       => $post['tanggal'],
            'nilai'                         => (int)str_replace([',', '.', ' '], '', $post['nilai'] ?? 0),
            'nama_influencer'               => $post['nama_influencer'] ?? null,
            'file_name'                     => $fileName,
            'original_name'                 => $origName,
            'serah_terima_file_name'        => $stFileName,
            'serah_terima_original_name'    => $stOrigName,
            'bukti_terpasang_file_name'     => $btFileName,
            'bukti_terpasang_original_name' => $btOrigName,
            'catatan'                       => $post['catatan'] ?? null,
            'created_by'                    => $this->currentUser()['id'],
        ]);
        if (! $rid) return redirect()->back()->with('error', 'Gagal menyimpan realisasi.');
        foreach ($pendingMoves as [$f, $d, $n]) { $f->move($d, $n); \App\Libraries\ImageCompressor::compress($d . '/' . $n); }

        CreativeTemaService::tandaiDikerjakan('standalone', $id);
        ActivityLog::write('create', 'creative_standalone_realisasi', (string)$id, $post['tanggal'], []);

        return redirect()->to('/creative#item-' . $id . '-s')->with('success', 'Realisasi berhasil ditambahkan.');
    }

    public function deleteRealisasi(int $id, int $rid)
    {
        if (!$this->canEditMenu('creative_main')) {
            return redirect()->to('/creative')->with('error', 'Akses ditolak.');
        }

        if ($r = $this->tolakBilaTemaTutup($id)) return $r;

        $model = new CreativeRealisasiModel();
        $row   = $model->find($rid);

        $db = db_connect();
        $db->transStart();
        $model->delete($rid);
        $db->transComplete();

        if ($db->transStatus() && $row) {
            $dir = FCPATH . 'uploads/creative-standalone-realisasi/' . $id . '/';
            foreach (['file_name', 'serah_terima_file_name', 'bukti_terpasang_file_name'] as $col) {
                if ($row[$col] && file_exists($dir . $row[$col])) unlink($dir . $row[$col]);
            }
        }

        ActivityLog::write('delete', 'creative_standalone_realisasi', (string)$rid, '', []);

        return redirect()->to('/creative#item-' . $id . '-s')->with('success', 'Realisasi berhasil dihapus.');
    }

    public function storeInsight(int $id)
    {
        if (!$this->canEditMenu('creative_main')) {
            return redirect()->to('/creative')->with('error', 'Akses ditolak.');
        }

        if ($r = $this->tolakBilaTemaTutup($id)) return $r;

        $post     = $this->request->getPost();
        $dir      = $this->insightDir($id);
        $item     = (new CreativeItemModel())->find($id);
        $fileName = null;
        $origName = null;

        $file = $this->request->getFile('screenshot');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            if ($err = $this->validateUpload($file, self::MIME_IMAGE, 10)) {
                return redirect()->back()->with('error', $err);
            }
            $fileName = 'insight_s_' . $id . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $this->safeExt($file);
            $origName = $file->getClientName();
        }

        $iid = (new CreativeInsightModel())->insert([
            'creative_item_id' => $id,
            'tanggal'          => $post['tanggal'],
            'platform'         => $post['platform'] ?? ($item['platform'] ?? null),
            'reach'            => (int)($post['reach']            ?? 0),
            'impressions'      => (int)($post['impressions']      ?? 0),
            'views'            => (int)($post['views']            ?? 0),
            'likes'            => (int)($post['likes']            ?? 0),
            'comments'         => (int)($post['comments']         ?? 0),
            'shares'           => (int)($post['shares']           ?? 0),
            'saves'            => (int)($post['saves']            ?? 0),
            'followers_gained' => (int)($post['followers_gained'] ?? 0),
            'file_name'        => $fileName,
            'original_name'    => $origName,
            'catatan'          => $post['catatan'] ?? null,
            'created_by'       => $this->currentUser()['id'],
        ]);
        if (! $iid) return redirect()->back()->with('error', 'Gagal menyimpan insight.');
        if ($file && $file->isValid() && !$file->hasMoved() && $fileName) { $file->move($dir, $fileName); \App\Libraries\ImageCompressor::compress($dir . '/' . $fileName); }

        CreativeTemaService::tandaiDikerjakan('standalone', $id);
        ActivityLog::write('create', 'creative_standalone_insight', (string)$id, $post['tanggal'], []);

        return redirect()->to('/creative#item-' . $id . '-s')->with('success', 'Insight berhasil disimpan.');
    }

    public function deleteInsight(int $id, int $iid)
    {
        if (!$this->canEditMenu('creative_main')) {
            return redirect()->to('/creative')->with('error', 'Akses ditolak.');
        }

        if ($r = $this->tolakBilaTemaTutup($id)) return $r;

        $model = new CreativeInsightModel();
        $row   = $model->find($iid);
        $model->delete($iid);

        if ($row && $row['file_name']) {
            $path = FCPATH . 'uploads/creative-standalone-insight/' . $id . '/' . $row['file_name'];
            if (file_exists($path)) unlink($path);
        }

        ActivityLog::write('delete', 'creative_standalone_insight', (string)$iid, '', []);

        return redirect()->to('/creative#item-' . $id . '-s')->with('success', 'Insight berhasil dihapus.');
    }
}
