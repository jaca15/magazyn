<?php
// auth.php - pomocnicze funkcje autoryzacji
// Użyj: require 'auth.php'; potem require_login(); aby chronić stronę.

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
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

// ===== CSRF =====

/**
 * Zwraca (i w razie potrzeby generuje) token CSRF dla bieżącej sesji.
 */
function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Weryfikuje token CSRF.
 * Sprawdza (w kolejności): $_POST['csrf_token'], nagłówek HTTP X-CSRF-Token,
 * oraz opcjonalnie tablicę danych (np. zdekodowane JSON body).
 * Zwraca true jeśli token jest poprawny.
 *
 * @param array|null $extra Dodatkowa tablica danych do sprawdzenia (np. json_decode output)
 */
function csrf_verify(?array $extra = null): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $expected = $_SESSION['csrf_token'] ?? '';
    if ($expected === '') {
        return false;
    }
    $submitted = $_POST['csrf_token']
        ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')
        ?? ($extra['csrf_token'] ?? '');
    return hash_equals($expected, (string)$submitted);
}

/**
 * Renderuje ukryte pole formularza z tokenem CSRF.
 * Użycie: <?= csrf_field() ?>
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}
?>