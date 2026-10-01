<?php

namespace App\Controllers\Api;

use App\Libraries\AiCopotPassword;
use App\Libraries\AiLog;
use App\Models\AiDeviceModel;
use App\Models\AiSessionModel;

/**
 * Pemantauan AI — endpoint PENERIMA kiriman laptop.
 *
 * Laptop tim menjalankan hook Claude Code yang menyapu baris JSONL transkrip
 * dan mem-POST-nya ke sini apa adanya; SELURUH tafsir ada di {@see AiLog},
 * bukan di laptop, agar perubahan format transkrip antar versi cukup
 * diperbaiki di server (lihat AiLog dan public/ai-monitor/).
 *
 * Auth BUKAN lewat ApiTokenModel (itu untuk aplikasi karyawan): tiap laptop
 * punya token perangkat sendiri yang hanya disimpan hash-nya di ai_devices.
 * Token asli hanya ada di berkas pemasang; mengunduh pemasang lagi memutar
 * token lama. Karena itu endpoint ini tidak memanggil requireAuth().
 */
class AiMonitorController extends BaseApiController
{
    /**
     * Enrollment otomatis — laptop mendaftarkan diri TANPA kunci.
     *
     * POST /api/ai-monitor/enroll, body JSON:
     *   { machine_id, host, account, label }   (enroll_key diabaikan bila ada)
     *
     * Tak ada kunci bersama: perangkat baru masuk daftar sebagai "menunggu
     * persetujuan" (aktif=0, disetujui_at=NULL) dan baru boleh mengirim data
     * setelah IT menekan "Setujui" di dashboard. Token kirim per-laptop tetap
     * diterbitkan unik di sini dan hanya dikembalikan SEKALI ke agen pemasang.
     *
     * Idempoten lewat machine_id: pemasangan ulang pada mesin yang sama HANYA
     * MEROTASI token (hash baru) — aktif, disetujui_at, employee_id, dan label
     * tak disentuh, sehingga status persetujuan dan alias dari IT tak hilang.
     */
    public function enroll()
    {
        $body = json_decode((string) $this->request->getBody(), true);
        if (! is_array($body)) {
            return $this->error('Body JSON tidak valid.', 400);
        }

        $machineId = trim((string) ($body['machine_id'] ?? ''));
        if ($machineId === '') {
            return $this->error('machine_id wajib diisi.', 400);
        }
        $machineId = substr($machineId, 0, 100);

        $host    = isset($body['host'])    ? mb_substr((string) $body['host'], 0, 100)    : null;
        $account = isset($body['account']) ? mb_substr((string) $body['account'], 0, 100) : null;
        $label   = trim((string) ($body['label'] ?? ''));
        if ($label === '') $label = $host ?: $machineId;
        $label = substr($label, 0, 100);

        // Token mentah: 32 byte acak → 64 hex. Yang disimpan hanya sha256-nya;
        // nilai mentah hanya dikirim sekali ke agen ini (lihat buatPerangkat).
        $token = bin2hex(random_bytes(32));
        $now   = date('Y-m-d H:i:s');

        $model    = new AiDeviceModel();
        $existing = $model->where('machine_id', $machineId)->first();

        if ($existing) {
            // ROTASI TOKEN SAJA. aktif/disetujui_at/employee_id/label TIDAK
            // disentuh — pemasangan ulang tak boleh mengubah status persetujuan
            // atau menghapus tautan/alias yang sudah dibuat IT. (host/akun pun
            // dibiarkan; nanti diperbarui sendiri lewat ingest.)
            $model->update((int) $existing['id'], [
                'token_hash'  => hash('sha256', $token),
                'enrolled_at' => $now,
            ]);
            $deviceId = (int) $existing['id'];
            $disetujui = ! empty($existing['disetujui_at']);
        } else {
            // Perangkat BARU → menunggu persetujuan: aktif=0, disetujui_at NULL.
            $deviceId = (int) $model->insert([
                'employee_id'   => null,
                'label'         => $label,
                'machine_id'    => $machineId,
                'token_hash'    => hash('sha256', $token),
                'aktif'         => 0,
                'disetujui_at'  => null,
                'host_terakhir' => $host,
                'akun_terakhir' => $account,
                'enrolled_at'   => $now,
            ], true);
            $disetujui = false;
        }

        // Token mentah hanya dikembalikan di respons ini, tak pernah lagi.
        // copot_hash disalurkan agar agen bisa memvalidasi password copot
        // secara offline (hash aman dikirim; perangkat dikelola IT).
        return $this->json([
            'success'      => true,
            'device_token' => $token,
            'device_id'    => $deviceId,
            'copot_hash'   => AiCopotPassword::hashNow(),
            'status'       => $disetujui ? 'aktif' : 'pending',
        ]);
    }

    public function ingest()
    {
        // ── Auth perangkat ───────────────────────────────────────────────
        // Header Bearer, cocokkan hash-nya SAJA (tanpa menyaring aktif di
        // query). Token tak dikenal → 401, supaya agen tahu tokennya basi dan
        // melakukan enroll ulang. Status aktif ditangani SETELAH ini.
        $header = $this->request->getHeaderLine('Authorization');
        if (! $header || ! str_starts_with($header, 'Bearer ')) {
            return $this->error('Token perangkat tidak ada.', 401);
        }
        $token  = trim(substr($header, 7));
        $hash   = hash('sha256', $token);
        $device = (new AiDeviceModel())->where('token_hash', $hash)->first();
        if (! $device) {
            return $this->error('Token perangkat tidak dikenal.', 401);
        }

        // ── Belum disetujui / dinonaktifkan (aktif=0) ────────────────────
        // JANGAN simpan apa pun, tapi balas 200 (BUKAN 401) agar agen tidak
        // menghapus tokennya — ia hanya menunggu IT menekan "Setujui". Begitu
        // aktif=1, kiriman berikutnya diproses normal.
        if (empty($device['aktif'])) {
            return $this->json([
                'success'       => true,
                'status'        => 'pending',
                'diterima'      => 0,
                'sesi'          => 0,
                'blokir'        => false,
                'blokir_alasan' => null,
                'copot_hash'    => AiCopotPassword::hashNow(),
            ]);
        }

        // ── Batas ukuran ─────────────────────────────────────────────────
        // Dibaca dari body mentah SEBELUM decode: menolak lebih awal lebih
        // murah daripada mengurai JSON raksasa hanya untuk membuangnya. Skrip
        // laptop memecah kirimannya jauh di bawah ambang ini.
        $raw = (string) $this->request->getBody();
        if (strlen($raw) > AiLog::MAKS_KIRIMAN) {
            return $this->error('Kiriman terlalu besar.', 413);
        }

        $body  = json_decode($raw, true);
        if (! is_array($body)) {
            return $this->error('Body JSON tidak valid.', 400);
        }
        $lines = $body['lines'] ?? [];
        if (! is_array($lines)) $lines = [];

        $deviceId   = (int) $device['id'];
        $employeeId = (int) $device['employee_id'];

        $sessModel  = new AiSessionModel();

        // Keadaan per sesi yang tersentuh batch ini. Dikumpulkan di memori
        // lalu ditulis sekali di akhir — satu UPDATE per sesi, bukan satu per
        // baris, dan agregatnya dihitung ulang dari tabel anak (bukan dijumlah
        // di sini) agar pengiriman ulang tidak pernah menggandakan hitungan.
        $sesi = []; // session_uuid => keadaan

        $db = $this->db;
        $db->transStart();

        foreach ($lines as $baris) {
            if (! is_array($baris)) continue;

            // sessionId ada di ROOT baris — termasuk pada baris sintetis
            // ai-title / last-prompt yang message-nya tak memuat sessionId.
            $uuid = (string) ($baris['sessionId'] ?? '');
            if ($uuid === '') continue;

            // Upsert sesi berdasarkan UNIQUE(device_id, session_uuid).
            if (! isset($sesi[$uuid])) {
                $row = $sessModel->where('device_id', $deviceId)
                    ->where('session_uuid', $uuid)->first();
                if (! $row) {
                    $cwd = isset($baris['cwd']) ? (string) $baris['cwd'] : null;
                    $id  = (int) $sessModel->insert([
                        'device_id'    => $deviceId,
                        'employee_id'  => $employeeId,
                        'session_uuid' => $uuid,
                        'cwd'          => $cwd,
                        'proyek'       => AiLog::namaProyek($cwd),
                        'git_branch'   => isset($baris['gitBranch']) ? (string) $baris['gitBranch'] : null,
                        'versi_cc'     => isset($baris['version']) ? (string) $baris['version'] : null,
                        'model'        => $baris['message']['model'] ?? null,
                        'mulai_at'     => $this->waktu($baris),
                        'terakhir_at'  => $this->waktu($baris),
                    ], true);
                    $row = $sessModel->find($id);
                }
                $sesi[$uuid] = [
                    'id'    => (int) $row['id'],
                    'row'   => $row,
                    'maxTs' => $row['terakhir_at'] ?? null,
                    'minTs' => $row['mulai_at'] ?? null,
                    'judul' => ['prio' => 0, 'teks' => null],
                ];
            }

            $sesiId = $sesi[$uuid]['id'];
            $ts     = $this->waktu($baris);

            // terakhir_at = stempel TERBESAR; mulai_at = terkecil. Baris bisa
            // tiba tak berurutan antar kiriman, jadi keduanya dijaga di sini.
            if ($ts !== null) {
                if ($sesi[$uuid]['maxTs'] === null || $ts > $sesi[$uuid]['maxTs']) $sesi[$uuid]['maxTs'] = $ts;
                if ($sesi[$uuid]['minTs'] === null || $ts < $sesi[$uuid]['minTs']) $sesi[$uuid]['minTs'] = $ts;
            }

            // Metadata yang mungkin baru muncul di baris belakangan (mis. baris
            // pertama yang terkirim adalah ai-title yang tak punya cwd). Dicatat
            // untuk mengisi kolom sesi yang masih kosong di akhir.
            if (! empty($baris['cwd']))     $sesi[$uuid]['cwd']     = (string) $baris['cwd'];
            if (! empty($baris['gitBranch'])) $sesi[$uuid]['git_branch'] = (string) $baris['gitBranch'];
            if (! empty($baris['version'])) $sesi[$uuid]['versi_cc'] = (string) $baris['version'];
            if (! empty($baris['message']['model'])) $sesi[$uuid]['model'] = (string) $baris['message']['model'];

            // Entri (prompt / balasan / alat). INSERT IGNORE: duplikat dari
            // pengiriman ulang ditelan kunci unik (ai_session_id, uuid).
            $entri = AiLog::uraiEntri($baris);
            if ($entri !== null) {
                $db->table('ai_entries')->ignore(true)->insert([
                    'ai_session_id' => $sesiId,
                    'uuid'          => $entri['uuid'],
                    'jenis'         => $entri['jenis'],
                    // Kolom waktu NOT NULL — baris tanpa timestamp (jarang)
                    // diberi waktu terima agar tidak menolak seluruh entri.
                    'waktu'         => $entri['waktu'] ?? date('Y-m-d H:i:s'),
                    'alat'          => $entri['alat'],
                    'sasaran'       => $entri['sasaran'],
                    'isi'           => $entri['isi'],
                ]);
            }

            // Pemakaian token, dikunci (ai_session_id, request_id).
            $usage = AiLog::uraiUsage($baris);
            if ($usage !== null) {
                $db->table('ai_usage')->ignore(true)->insert([
                    'ai_session_id' => $sesiId,
                    'request_id'    => $usage['request_id'],
                    'waktu'         => $usage['waktu'] ?? date('Y-m-d H:i:s'),
                    'model'         => $usage['model'],
                    'token_masuk'   => $usage['masuk'],
                    'token_keluar'  => $usage['keluar'],
                ]);
            }

            // Judul sesi: ai-title diprioritaskan di atas last-prompt. Keduanya
            // baris sintetis dari hook laptop, bukan entri transkrip.
            $tipe = $baris['type'] ?? null;
            if ($tipe === 'ai-title' && ! empty($baris['aiTitle'])) {
                $sesi[$uuid]['judul'] = ['prio' => 2, 'teks' => (string) $baris['aiTitle']];
            } elseif ($tipe === 'last-prompt' && ! empty($baris['lastPrompt'])
                && $sesi[$uuid]['judul']['prio'] < 2) {
                $sesi[$uuid]['judul'] = ['prio' => 1, 'teks' => (string) $baris['lastPrompt']];
            }
        }

        // ── Hitung ulang agregat + tulis sekali per sesi ─────────────────
        foreach ($sesi as $s) {
            $sesiId = $s['id'];

            $jmlPrompt = $db->table('ai_entries')
                ->where('ai_session_id', $sesiId)->where('jenis', 'prompt')->countAllResults();
            $jmlAlat = $db->table('ai_entries')
                ->where('ai_session_id', $sesiId)->where('jenis', 'alat')->countAllResults();
            $tok = $db->table('ai_usage')
                ->selectSum('token_masuk', 'm')->selectSum('token_keluar', 'k')
                ->where('ai_session_id', $sesiId)->get()->getRowArray();

            $upd = [
                'jml_prompt'   => $jmlPrompt,
                'jml_alat'     => $jmlAlat,
                'token_masuk'  => (int) ($tok['m'] ?? 0),
                'token_keluar' => (int) ($tok['k'] ?? 0),
            ];
            if ($s['maxTs'] !== null) $upd['terakhir_at'] = $s['maxTs'];
            if ($s['minTs'] !== null) $upd['mulai_at']    = $s['minTs'];

            // Isi metadata hanya bila kolomnya masih kosong — jangan menimpa
            // nilai yang sudah benar dengan null dari baris sintetis.
            foreach (['cwd', 'git_branch', 'versi_cc', 'model'] as $k) {
                if (isset($s[$k]) && empty($s['row'][$k])) $upd[$k] = $s[$k];
            }
            if (isset($upd['cwd'])) $upd['proyek'] = AiLog::namaProyek($upd['cwd']);

            if ($s['judul']['prio'] > 0) {
                $upd['judul'] = mb_substr($s['judul']['teks'], 0, 255);
            }

            $sessModel->update($sesiId, $upd);
        }

        // Jejak perangkat terakhir melapor — dipakai halaman rekap untuk
        // menandai laptop yang sudah lama senyap.
        (new AiDeviceModel())->update($deviceId, [
            'host_terakhir' => isset($body['host'])    ? mb_substr((string) $body['host'], 0, 100) : $device['host_terakhir'],
            'akun_terakhir' => isset($body['account']) ? mb_substr((string) $body['account'], 0, 100) : $device['akun_terakhir'],
            'lapor_at'      => date('Y-m-d H:i:s'),
        ]);

        $db->transComplete();
        if ($db->transStatus() === false) {
            return $this->error('Gagal menyimpan kiriman.', 500);
        }

        // Status blokir ikut dikirim di respons sukses. Agen laptop membacanya
        // dan menegakkan stop sementara (berhenti menyapu/mengirim) tanpa
        // kehilangan tokennya — blokir bisa dicabut dari halaman perangkat,
        // dan begitu diblokir=0 field ini kembali false pada kiriman berikut.
        return $this->json([
            'success'       => true,
            'status'        => 'aktif',
            'diterima'      => count($lines),
            'sesi'          => count($sesi),
            'blokir'        => (bool) $device['diblokir'],
            // Alasan ditampilkan agen di notifikasi ke pemakai ("dinonaktifkan
            // oleh Tim IT"). null bila tidak diblokir atau alasan kosong.
            'blokir_alasan' => $device['diblokir'] ? ($device['alasan_blokir'] ?: null) : null,
            // Hash password copot terkini — agen menyimpannya tiap laporan agar
            // perubahan password dari MIC tersinkron. null bila belum diset.
            'copot_hash'    => AiCopotPassword::hashNow(),
        ]);
    }

    /** Stempel waktu 'Y-m-d H:i:s' dari field timestamp baris, atau null. */
    private function waktu(array $b): ?string
    {
        $t = $b['timestamp'] ?? null;
        if (! $t) return null;
        $ts = strtotime((string) $t);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }
}
