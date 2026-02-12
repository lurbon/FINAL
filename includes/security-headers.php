<?php
/**
 * Headers de sécurité HTTP pour les pages publiques
 *
 * Protège contre : XSS, Clickjacking, MIME sniffing, MITM
 * À inclure au début de chaque page publique.
 */

// X-Frame-Options - Protection contre le Clickjacking
header("X-Frame-Options: SAMEORIGIN");

// X-Content-Type-Options - Protection contre le MIME sniffing
header("X-Content-Type-Options: nosniff");

// Strict-Transport-Security (HSTS) - Force HTTPS
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
}

// X-XSS-Protection - Protection XSS pour navigateurs anciens
header("X-XSS-Protection: 1; mode=block");

// Referrer-Policy
header("Referrer-Policy: strict-origin-when-cross-origin");

// Content Security Policy (adaptée aux pages publiques avec Google Fonts)
header(
    "Content-Security-Policy: " .
    "default-src 'self'; " .
    "script-src 'self' 'unsafe-inline'; " .
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
    "font-src 'self' https://fonts.gstatic.com; " .
    "img-src 'self' data:; " .
    "connect-src 'self'; " .
    "frame-ancestors 'self'; " .
    "base-uri 'self'; " .
    "form-action 'self'"
);

// Permissions-Policy - Désactive les API sensibles
header(
    "Permissions-Policy: " .
    "geolocation=(), " .
    "microphone=(), " .
    "camera=(), " .
    "payment=()"
);
