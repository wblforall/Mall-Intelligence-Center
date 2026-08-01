<?php

namespace App\Libraries;

class SectionConfig
{
    // ── SUMBER TUNGGAL daftar menu ───────────────────────────────
    // Menu key → label. Klasifikasi standalone vs per-event ditentukan
    // oleh array mana sebuah key berada — tidak boleh di-hardcode ulang di
    // tempat lain (view/sidebar). Untuk daftar key gunakan standaloneKeys()
    // / perEventKeys(). Tambah menu baru = cukup edit salah satu array ini.

    // Standalone (Main Sidebar)
    const STANDALONE_MENUS = [
        'events'             => 'Daftar Event',
        'loyalty_main'       => 'Loyalty — Standalone',
        'creative_main'      => 'Creative — Standalone',
        'vm_main'            => 'VM — Standalone',
        'sponsorship_main'   => 'Sponsorship — Standalone',
        'people_dev'         => 'People Development',
        'hr_main'            => 'HR (Karyawan, Org Chart, Appraisal)',
        'traffic'            => 'Daily Traffic',
        'parking_live'       => 'Parkir — Live (real-time)',
        'parking_vehicles'   => 'Parkir — Traffic Kendaraan',
        'parking_revenue'    => 'Parkir — Revenue',
        'legal'              => 'Legal',
        'work_report'        => 'Progress Report',
    ];

    // Per-Event (Event Sub-menu)
    const PER_EVENT_MENUS = [
        'summary'       => 'Event Summary',
        'content'       => 'Content & Rundown',
        'loyalty'       => 'Loyalty — Per Event',
        'creative'      => 'Creative — Per Event',
        'vm'            => 'VM — Per Event',
        'exhibitors'    => 'Exhibition',
        'sponsors'      => 'Sponsorship',
        'budget'        => 'Budget',
    ];

    // Peta lengkap (standalone dulu, lalu per-event) — turunan, jangan diedit langsung.
    const MENU_LABELS = self::STANDALONE_MENUS + self::PER_EVENT_MENUS;

    /** Daftar key menu standalone (main sidebar). */
    public static function standaloneKeys(): array
    {
        return array_keys(self::STANDALONE_MENUS);
    }

    /** Daftar key menu per-event (sub-menu event). */
    public static function perEventKeys(): array
    {
        return array_keys(self::PER_EVENT_MENUS);
    }

    /** True jika $key adalah menu standalone. */
    public static function isStandalone(string $key): bool
    {
        return isset(self::STANDALONE_MENUS[$key]);
    }

    // Section labels (for traffic filtering — kept for future use)
    const SECTION_LABELS = [
        'all' => 'Semua Data',
    ];

    // Mall options
    const MALL_OPTIONS = [
        'ewalk'     => 'eWalk',
        'pentacity' => 'Pentacity',
        'keduanya'  => 'eWalk & Pentacity',
    ];
}
