<?php

namespace App\Libraries;

class SectionConfig
{
    // Menu keys → human-readable labels
    // Keys prefixed with _main = standalone/main-menu access
    // Keys without prefix   = per-event sub-menu access
    const MENU_LABELS = [
        // ── Standalone (Main Sidebar) ────────────────────────────
        'events'        => 'Daftar Event',
        'loyalty_main'       => 'Loyalty — Standalone',
        'creative_main'      => 'Creative — Standalone',
        'vm_main'            => 'VM — Standalone',
        'sponsorship_main'   => 'Sponsorship — Standalone',
        'people_dev'         => 'People Development',
        'traffic'            => 'Daily Traffic',
        // ── Per-Event (Event Sub-menu) ───────────────────────────
        'summary'       => 'Event Summary',
        'content'       => 'Content & Rundown',
        'loyalty'       => 'Loyalty — Per Event',
        'creative'      => 'Creative — Per Event',
        'vm'            => 'VM — Per Event',
        'exhibitors'    => 'Exhibition',
        'sponsors'      => 'Sponsorship',
        'budget'        => 'Budget',
    ];

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
