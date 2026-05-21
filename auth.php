<?php
// auth.php - pomocnicze funkcje autoryzacji
// Użyj: require 'auth.php'; potem require_login(); aby chronić stronę.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function zalogowany_user() {
    return $_SESSION['user'] ?? null;
}

function czy_zalogowany() {
    return (bool) zalogowany_user();
}

function require_login() {
    if (!czy_zalogowany()) {
        header('Location: logowanie.php');
        exit;
    }
}

function czy_admin() {
    $u = zalogowany_user();
    return $u && isset($u['rola']) && $u['rola'] === 'admin';
}

function require_admin() {
    if (!czy_admin()) {
        http_response_code(403);
        echo "Brak uprawnień (wymagane konto administratora).";
        exit;
    }
}
?>