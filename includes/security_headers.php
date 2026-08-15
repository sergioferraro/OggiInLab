<?php
/*
 * OggiInLab – Header di sicurezza centralizzati
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under la MIT License
 *
 * Da includere all'inizio di ogni risposta HTTP (vedi includes/session.php).
 * Le CSP ammettono i CDN già usati dall'app (jQuery, jsDelivr, cdnjs, Google Fonts)
 * e gli script inline presenti nelle pagine (necessari per Bootstrap/interazione).
 */

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header(
        "Content-Security-Policy: "
        . "default-src 'self'; "
        . "script-src 'self' 'unsafe-inline' https://code.jquery.com https://cdn.jsdelivr.net; "
        . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; "
        . "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; "
        . "img-src 'self' data:; "
        . "connect-src 'self'; "
        . "object-src 'none'; "
        . "base-uri 'self'; "
        . "frame-ancestors 'none'"
    );
}
