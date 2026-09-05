<?php

const TITAN_SESSION_LIFETIME = 60 * 60 * 24 * 30;

function titan_start_session(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    ini_set('session.gc_maxlifetime', (string) TITAN_SESSION_LIFETIME);
    session_set_cookie_params([
        'lifetime' => TITAN_SESSION_LIFETIME,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

