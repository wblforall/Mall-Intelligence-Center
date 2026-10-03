<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseConfig
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     *
     * @var array<string, class-string|list<class-string>> [filter_name => classname]
     *                                                     or [filter_name => [classname1, classname2, ...]]
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        // Sama dengan invalidchars, tapi pesan penolakannya ringkas (tanpa isi
        // utuh nilai) agar log tidak membengkak. Dipakai sebagai filter global.
        'invalidchars_ringkas' => \App\Filters\InvalidCharsRingkas::class,
        'secureheaders' => SecureHeaders::class,
        'auth'          => \App\Filters\AuthFilter::class,
        'sechead'       => \App\Filters\SecurityHeaders::class,
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     *
     * @var array<string, array<string, array<string, string>>>|array<string, list<string>>
     */
    public array $globals = [
        'before' => [
            // 'honeypot',
            // API uses stateless Bearer-token auth (no cookies) → exempt from session CSRF.
            'csrf' => ['except' => ['api/*']],
            // Rute ingest Pemantauan AI dikecualikan: body-nya JSON transkrip
            // (bisa ratusan KB) yang dinormalisasi sendiri di controller
            // (mb_scrub + JSON_INVALID_UTF8_SUBSTITUTE). Lihat AiMonitorController::ingest.
            'invalidchars_ringkas' => ['except' => ['api/ai-monitor/ingest']],
        ],
        'after' => [
            'toolbar',
            // 'honeypot',
            'secureheaders',
            'sechead',
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     *
     * Example:
     * 'post' => ['foo', 'bar']
     *
     * If you use this, you should disable auto-routing because auto-routing
     * permits any HTTP method to access a controller. Accessing the controller
     * with a method you don't expect could bypass the filter.
     *
     * @var array<string, list<string>>
     */
    public array $methods = [];

    /**
     * List of filter aliases that should run on any
     * before or after URI patterns.
     *
     * Example:
     * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
     *
     * @var array<string, array<string, list<string>>>
     */
    public array $filters = [];
}
