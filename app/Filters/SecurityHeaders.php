<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Header keamanan tambahan di luar yang sudah diset filter `secureheaders` CI4
 * (X-Frame-Options, X-Content-Type-Options, Referrer-Policy, dll).
 *
 * Ditambahkan:
 *  - Permissions-Policy   : matikan fitur browser kuat yang tidak dipakai app.
 *  - Strict-Transport-Security (HSTS) : hanya saat HTTPS (produksi).
 *  - Cross-Origin-Opener-Policy : isolasi browsing context (aman, tanpa COEP).
 *  - Content-Security-Policy-Report-Only : allowlist sumber ketat sebagai basis.
 *    Mode Report-Only = TIDAK memblokir apa pun, hanya melapor pelanggaran ke
 *    console browser. Dipakai untuk mengumpulkan data sebelum CSP di-enforce.
 *    `'unsafe-inline'` masih diizinkan karena app penuh inline <style>/<script>;
 *    yang dikunci: object-src, base-uri, form-action, frame-ancestors.
 */
class SecurityHeaders implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // no-op
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Jangan ganggu response unduhan file / non-HTML
        $ctype = $response->getHeaderLine('Content-Type');
        if ($ctype && stripos($ctype, 'text/html') === false) {
            return;
        }

        $response->setHeader(
            'Permissions-Policy',
            'geolocation=(), camera=(), microphone=(), payment=(), usb=(), '
            . 'magnetometer=(), gyroscope=(), accelerometer=(), interest-cohort=()'
        );

        $response->setHeader('Cross-Origin-Opener-Policy', 'same-origin');

        // HSTS hanya bermakna & aman di HTTPS (produksi)
        if ($request->isSecure()) {
            $response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "img-src 'self' data: blob:",
            "font-src 'self' data: https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "connect-src 'self' https://api.open-meteo.com https://cdn.jsdelivr.net",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ]);
        $response->setHeader('Content-Security-Policy-Report-Only', $csp);
    }
}
