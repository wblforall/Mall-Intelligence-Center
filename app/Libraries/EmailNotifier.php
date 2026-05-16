<?php

namespace App\Libraries;

class EmailNotifier
{
    private static function mailer(): \CodeIgniter\Email\Email
    {
        return \Config\Services::email();
    }

    public static function send(string $to, string $subject, string $body): bool
    {
        $mail = self::mailer();
        $mail->setTo($to);
        $mail->setSubject($subject);
        $mail->setMessage($body);
        $mail->setMailType('html');
        return $mail->send(false);
    }

    public static function sendBulk(array $recipients, string $subject, callable $bodyFn): array
    {
        $results = ['sent' => 0, 'failed' => 0, 'errors' => []];
        foreach ($recipients as $to) {
            if (empty($to)) continue;
            $body = $bodyFn($to);
            if (self::send($to, $subject, $body)) {
                $results['sent']++;
            } else {
                $results['failed']++;
                $results['errors'][] = $to;
            }
        }
        return $results;
    }

    // ── Template helpers ──────────────────────────────────────────────────

    public static function wrap(string $title, string $content): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="id">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
        <body style="font-family:Arial,sans-serif;background:#f3f4f6;margin:0;padding:24px">
        <div style="max-width:520px;margin:auto;background:#fff;border-radius:8px;border:1px solid #e5e7eb;overflow:hidden">
            <div style="background:#1e3a5f;padding:16px 24px">
                <div style="color:#93c5fd;font-size:10pt;letter-spacing:.05em">MALL INTELLIGENCE CENTER</div>
                <div style="color:#fff;font-size:14pt;font-weight:700;margin-top:2px">{$title}</div>
            </div>
            <div style="padding:24px;color:#374151;font-size:10.5pt;line-height:1.6">
                {$content}
            </div>
            <div style="background:#f9fafb;padding:12px 24px;border-top:1px solid #e5e7eb;font-size:8.5pt;color:#9ca3af">
                PT. Wulandari Bangun Laksana Tbk. &mdash; Email otomatis, jangan dibalas.
            </div>
        </div>
        </body></html>
        HTML;
    }

    public static function button(string $url, string $label): string
    {
        return "<div style='text-align:center;margin:24px 0'>
            <a href='{$url}' style='background:#2563eb;color:#fff;padding:11px 28px;border-radius:6px;text-decoration:none;font-weight:600;font-size:10.5pt'>{$label}</a>
        </div>";
    }

    // ── PIP ───────────────────────────────────────────────────────────────

    public static function pipApprovalAtasan(array $plan, string $url): string
    {
        $judul    = htmlspecialchars($plan['judul']);
        $karyawan = htmlspecialchars($plan['employee_nama']);
        $mulai    = date('d M Y', strtotime($plan['tanggal_mulai']));
        $selesai  = date('d M Y', strtotime($plan['tanggal_selesai']));
        $content  = "
            <p>Yth. Bapak/Ibu,</p>
            <p>Anda diminta untuk menyetujui <strong>Performance Improvement Plan</strong> berikut:</p>
            <table style='width:100%;border-collapse:collapse;margin:12px 0;font-size:9.5pt'>
                <tr><td style='padding:4px 0;color:#6b7280;width:40%'>Karyawan</td><td style='padding:4px 0'><strong>{$karyawan}</strong></td></tr>
                <tr><td style='padding:4px 0;color:#6b7280'>Judul PIP</td><td style='padding:4px 0'>{$judul}</td></tr>
                <tr><td style='padding:4px 0;color:#6b7280'>Periode</td><td style='padding:4px 0'>{$mulai} – {$selesai}</td></tr>
            </table>
            <p>Klik tombol di bawah untuk melihat detail dan memberikan persetujuan:</p>
            " . self::button($url, 'Lihat & Setujui PIP') . "
            <p style='color:#6b7280;font-size:9pt'>Link ini bersifat personal. Jangan bagikan ke orang lain.</p>";
        return self::wrap('Persetujuan PIP', $content);
    }

    public static function pipApprovalKaryawan(array $plan, string $url): string
    {
        $judul   = htmlspecialchars($plan['judul']);
        $mulai   = date('d M Y', strtotime($plan['tanggal_mulai']));
        $selesai = date('d M Y', strtotime($plan['tanggal_selesai']));
        $content = "
            <p>Yth. Bapak/Ibu,</p>
            <p>Anda memiliki <strong>Performance Improvement Plan</strong> yang perlu ditinjau dan disetujui:</p>
            <table style='width:100%;border-collapse:collapse;margin:12px 0;font-size:9.5pt'>
                <tr><td style='padding:4px 0;color:#6b7280;width:40%'>Judul PIP</td><td style='padding:4px 0'>{$judul}</td></tr>
                <tr><td style='padding:4px 0;color:#6b7280'>Periode</td><td style='padding:4px 0'>{$mulai} – {$selesai}</td></tr>
            </table>
            " . self::button($url, 'Lihat & Tandatangani PIP') . "
            <p style='color:#6b7280;font-size:9pt'>Link ini bersifat personal. Jangan bagikan ke orang lain.</p>";
        return self::wrap('PIP Anda Menunggu Persetujuan', $content);
    }

    public static function pipReviewReminder(array $plan, string $nextDate): string
    {
        $judul    = htmlspecialchars($plan['judul']);
        $karyawan = htmlspecialchars($plan['employee_nama']);
        $tgl      = date('d M Y', strtotime($nextDate));
        $url      = base_url('people/pip/' . $plan['id']);
        $content  = "
            <p>Yth. Bapak/Ibu,</p>
            <p>Pengingat: jadwal <strong>review PIP</strong> berikut jatuh tempo besok.</p>
            <table style='width:100%;border-collapse:collapse;margin:12px 0;font-size:9.5pt'>
                <tr><td style='padding:4px 0;color:#6b7280;width:40%'>Karyawan</td><td style='padding:4px 0'><strong>{$karyawan}</strong></td></tr>
                <tr><td style='padding:4px 0;color:#6b7280'>Judul PIP</td><td style='padding:4px 0'>{$judul}</td></tr>
                <tr><td style='padding:4px 0;color:#6b7280'>Tanggal Review</td><td style='padding:4px 0'><strong>{$tgl}</strong></td></tr>
            </table>
            " . self::button($url, 'Buka Detail PIP') . "";
        return self::wrap('Reminder Review PIP', $content);
    }

    // ── TNA ───────────────────────────────────────────────────────────────

    public static function tnaFillLink(string $employeeName, string $periodName, string $url): string
    {
        $name   = htmlspecialchars($employeeName);
        $period = htmlspecialchars($periodName);
        $content = "
            <p>Yth. <strong>{$name}</strong>,</p>
            <p>Anda diminta mengisi <strong>Training Needs Analysis (TNA)</strong> untuk periode <strong>{$period}</strong>.</p>
            <p>Klik tombol di bawah untuk mengisi formulir penilaian:</p>
            " . self::button($url, 'Isi Formulir TNA') . "
            <p style='color:#6b7280;font-size:9pt'>Link ini bersifat personal dan hanya bisa digunakan satu kali per periode.</p>";
        return self::wrap('Formulir TNA — ' . $period, $content);
    }

    // ── Traffic Summary ───────────────────────────────────────────────────

    public static function trafficSummary(array $data, string $tanggal): string
    {
        $tgl     = date('d F Y', strtotime($tanggal));
        $rows    = '';
        $total   = 0;
        foreach ($data as $mall => $jumlah) {
            $rows  .= "<tr><td style='padding:6px 8px;border:1px solid #e5e7eb'>{$mall}</td><td style='padding:6px 8px;border:1px solid #e5e7eb;text-align:right'><strong>" . number_format($jumlah) . "</strong></td></tr>";
            $total += $jumlah;
        }
        $content = "
            <p>Berikut rekap traffic pengunjung untuk tanggal <strong>{$tgl}</strong>:</p>
            <table style='width:100%;border-collapse:collapse;font-size:9.5pt;margin:12px 0'>
                <thead><tr>
                    <th style='padding:6px 8px;border:1px solid #e5e7eb;background:#f8fafc;text-align:left'>Mall</th>
                    <th style='padding:6px 8px;border:1px solid #e5e7eb;background:#f8fafc;text-align:right'>Pengunjung</th>
                </tr></thead>
                <tbody>{$rows}</tbody>
                <tfoot><tr>
                    <td style='padding:6px 8px;border:1px solid #e5e7eb;font-weight:700'>Total</td>
                    <td style='padding:6px 8px;border:1px solid #e5e7eb;text-align:right;font-weight:700'>" . number_format($total) . "</td>
                </tr></tfoot>
            </table>
            <p style='color:#6b7280;font-size:9pt'>Data diambil secara otomatis setiap hari pukul 07.00.</p>";
        return self::wrap('Traffic Summary — ' . $tgl, $content);
    }
}
