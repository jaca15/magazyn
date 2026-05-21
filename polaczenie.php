<?php
// polaczenie.php - połączenie z bazą danych
// Konfiguracja czytana z pliku .env (patrz .env.example).
// NIE wpisuj danych dostępowych bezpośrednio w kodzie.

(function () {
    $envFile = __DIR__ . '/.env';
    if (!is_file($envFile)) {
        return;
    }
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));
        // Usuń cudzysłowy jeśli wartość jest w nie opakowana
        if (strlen($value) >= 2
            && (($value[0] === '"' && substr($value, -1) === '"')
                || ($value[0] === "'" && substr($value, -1) === "'"))
        ) {
            $value = substr($value, 1, -1);
        }
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
})();

$host    = $_ENV['DB_HOST']    ?? getenv('DB_HOST')    ?: '127.0.0.1';
$port    = (int)(($_ENV['DB_PORT']    ?? getenv('DB_PORT'))    ?: 3307);
$baza    = $_ENV['DB_NAME']    ?? getenv('DB_NAME')    ?: 'magazyn_sprzetu';
$user    = $_ENV['DB_USER']    ?? getenv('DB_USER')    ?: 'root';
$haslo   = ($_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD')) ?: '';
$charset = $_ENV['DB_CHARSET'] ?? getenv('DB_CHARSET') ?: 'utf8mb4';

$appEnv = strtolower(($_ENV['APP_ENV'] ?? getenv('APP_ENV')) ?: 'production');
$debug  = ($appEnv === 'development');

// W trybie produkcyjnym blokuj start jeśli hasło DB jest puste (domyślne root bez hasła)
if (!$debug && $haslo === '') {
    error_log('polaczenie.php: DB_PASSWORD nie jest ustawiony — ustaw zmienną w pliku .env');
    echo 'Błąd konfiguracji serwera.';
    exit;
}

$dsn = "mysql:host={$host};port={$port};dbname={$baza};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $haslo, $options);
} catch (PDOException $e) {
    if ($debug) {
        echo "Błąd połączenia z bazą danych: " . htmlspecialchars($e->getMessage());
    } else {
        echo "Błąd połączenia z bazą danych.";
    }
    exit;
}

// Nagłówki bezpieczeństwa HTTP (P3)
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}
?>