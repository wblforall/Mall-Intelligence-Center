<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Penautan FlowStore untuk CCD (Creative Concept & Design) — keputusan pemilik 18 Okt 2026: FlowStore menjadi SUMBER
 * realisasi biaya CCD; modul Creative hanya MENAMPILKAN biaya dari BTB yang tertaut ke item-nya.
 *
 * Hanya MENAMBAH (aditif):
 * - budget_fs_aturan.mal        : kualifier mal pada aturan kata kunci ('ewalk' | 'pentacity' | 'both' = skema tak dikenal);
 *                                 NULL = berlaku untuk semua mal (perilaku lama). Dipakai untuk T-Banner (pos berbeda per mal).
 * - budget_fs_aturan.pengganti  : 1 = "pengganti terakhir": hanya dipakai bila tak ada aturan biasa yang cocok.
 * - budget_fs_pemetaan.creative_scope / creative_item_id : tautan BTB -> item Creative (creative_items bila 'standalone',
 *                                 event_creative_items bila 'event'). creative_item_id 0 = dipilih eksplisit "tidak terkait Creative".
 * - Seed aturan rekomendasi dept CCD (hanya bila dept itu belum punya aturan kata kunci). Pos dicocokkan lewat NAMA pos;
 *   pos nonaktif dilewati mesin rekomendasi (Cover FOH/Backstage dan Subscription Adobe saat ini nonaktif).
 */
class CcdFlowstore extends Migration
{
    public function up()
    {
        $db = $this->db;
        if (! $db->fieldExists('mal', 'budget_fs_aturan')) {
            $this->forge->addColumn('budget_fs_aturan', [
                'mal'       => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true, 'after' => 'pos_nama'],
                'pengganti' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'mal'],
            ]);
        }
        if (! $db->fieldExists('creative_scope', 'budget_fs_pemetaan')) {
            $this->forge->addColumn('budget_fs_pemetaan', [
                'creative_scope'   => ['type' => 'ENUM', 'constraint' => ['standalone', 'event'], 'null' => true, 'after' => 'catatan_tinjau'],
                'creative_item_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'creative_scope'],
            ]);
            $db->query('ALTER TABLE budget_fs_pemetaan ADD INDEX idx_pem_creative (creative_scope, creative_item_id)');
        }

        $ccd = $db->table('departments')->select('id')->where('name', 'Creative Concept & Design')->get()->getRowArray();
        if ($ccd && ! $db->table('budget_fs_aturan')->where('dept_id', (int) $ccd['id'])->where('jenis', 'kata_kunci')->countAllResults()) {
            $now = date('Y-m-d H:i:s');
            $T = '/\bT ?BANNER\b|\bX ?BANNER\b/';
            // [sasaran, kunci, pos, mal, pengganti]. Urutan array = prioritas; pada NAMA barang, kata yang muncul paling awal menang
            // (seri -> urutan aturan); pengganti selalu terakhir.
            $aturan = [
                // Keperluan (keterangan order) — didahulukan; hanya tujuan yang tegas
                ['keperluan', 'GATE LOBBY UTAMA|KACA GATE LOBBY',            'Sticker Main Gate', null, 0],
                ['keperluan', 'GIVEAWAY|GIVE AWAY',                           'Giveaway', null, 0],
                ['keperluan', 'PRESS CONFERENCE|PRESCON|PRES CON',            'Press Conference', null, 0],
                ['keperluan', 'IG ADS|INSTAGRAM ADS',                         'IG Ads', null, 0],
                ['keperluan', 'KUOTA',                                        'Kuota HP Public Relation', null, 0],
                ['keperluan', 'ADMINISTRASI DEPARTEMEN CREATIVE|MEDIA CETAK', 'Materi cetak lainnya', null, 1],
                // Nama barang
                ['nama', 'STICKER LIFT',                                      'Sticker Lift', null, 0],
                ['nama', 'MAIN GATE|KACA LOBBY|GATE LOBBY',                   'Sticker Main Gate', null, 0],
                ['nama', '/ UK 117 CM X (40|185) /',                          'Sticker Main Gate', null, 0],
                ['nama', $T,                                                  'T-Banner 1x3m (e-Walk)', 'ewalk', 0],
                ['nama', $T,                                                  'T-Banner 1x2,4m (Pentacity)', 'pentacity', 0],
                ['nama', $T,                                                  '', 'both', 0],
                ['nama', 'HANGING|GIANT BANNER',                              'Hanging Banner', null, 0],
                ['nama', '/(?=.*NEON BOX)(?=.*BEACH GATE)/',                  'Neon Box Beach Gate', null, 0],
                ['nama', '/(?=.*NEON BOX)(?=.*FOODCOURT)/',                   'Neon Box Foodcourt', null, 0],
                ['nama', '/(?=.*ALBATROS)(?=.*FOODCOURT)|ALBATROS.*UK 59 4 CM X 84 1/', 'Neon Box Foodcourt', null, 0],
                ['nama', '/(?=.*COVER)(?=.*FOH)/',                            'Cover FOH', null, 0],
                ['nama', '/(?=.*COVER)(?=.*BACKSTAGE)/',                      'Cover Backstage', null, 0],
                ['nama', 'KOL|TALENT|ENDORSE',                                'KOL/Talent', null, 0],
                ['nama', 'GIVEAWAY|GIVE AWAY',                                'Giveaway', null, 0],
                ['nama', 'PRESS CONFERENCE|PRESCON|PRES CON',                 'Press Conference', null, 0],
                ['nama', 'KUOTA|PAKET DATA',                                  'Kuota HP Public Relation', null, 0],
                ['nama', 'IG ADS|INSTAGRAM ADS',                              'IG Ads', null, 0],
                ['nama', 'ADOBE',                                             'Subscription Adobe Creative Cloud', null, 0],
                ['nama', 'CANVA',                                             'Subscription Canva', null, 0],
                ['nama', 'CAPCUT',                                            'Subscription Capcut', null, 0],
                ['nama', 'ENVATO',                                            'Subscription Envato', null, 0],
                ['nama', 'FREEPIK',                                           'Subscription Freepik', null, 0],
                ['nama', 'GOOGLE ONE|GOOGLEONE',                              'Subscription GoogleOne', null, 0],
                ['nama', 'STICKER|BANNER|POSTER|ALBATROS|VINYL|WALL SIGN|FLEXI|CETAK|KERTAS|COVER|SPANDUK|BACKDROP|BALIHO|ID CARD|BRANDING', 'Materi cetak lainnya', null, 1],
            ];
            foreach ($aturan as $i => [$sasaran, $kunci, $pos, $mal, $pengganti]) {
                $db->table('budget_fs_aturan')->insert([
                    'dept_id' => (int) $ccd['id'], 'jenis' => 'kata_kunci', 'sasaran' => $sasaran, 'kunci' => $kunci, 'pos_id' => null,
                    'pos_nama' => $pos, 'mal' => $mal, 'pengganti' => $pengganti, 'urutan' => ($i + 1) * 10, 'dipakai_berapa_kali' => 0, 'created_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        $db = $this->db;
        if ($db->fieldExists('creative_scope', 'budget_fs_pemetaan')) {
            $db->query('ALTER TABLE budget_fs_pemetaan DROP INDEX idx_pem_creative');
            $this->forge->dropColumn('budget_fs_pemetaan', ['creative_scope', 'creative_item_id']);
        }
        if ($db->fieldExists('mal', 'budget_fs_aturan')) {
            $ccd = $db->table('departments')->select('id')->where('name', 'Creative Concept & Design')->get()->getRowArray();
            if ($ccd) $db->table('budget_fs_aturan')->where('dept_id', (int) $ccd['id'])->where('jenis', 'kata_kunci')->delete();
            $this->forge->dropColumn('budget_fs_aturan', ['mal', 'pengganti']);
        }
    }
}
