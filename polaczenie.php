<?php
// polaczenie.php - połączenie z bazą danych (TCP na porcie 3307)
// Dostosuj user/haslo jeśli potrzeba
$host = getenv('DB_HOST') ?: getenv('MYSQL_HOST') ?: '127.0.0.1'; // użyj 127.0.0.1 zamiast 'localhost' aby wymusić TCP
$port = (int)(getenv('DB_PORT') ?: getenv('MYSQL_PORT') ?: 3307); // ustawiony port
$baza = getenv('DB_NAME') ?: getenv('MYSQL_DATABASE') ?: '';
$user = getenv('DB_USER') ?: getenv('MYSQL_USER') ?: '';
$haslo = getenv('DB_PASSWORD') ?: getenv('MYSQL_PASSWORD') ?: ''; // ustaw hasło
$charset = 'utf8mb4';

if ($baza === '') {
    echo "Błąd konfiguracji bazy danych: brak nazwy bazy. Ustaw DB_NAME lub MYSQL_DATABASE (opcjonalnie także DB_HOST, DB_PORT, DB_USER, DB_PASSWORD).";
    exit;
}

$dsn = "mysql:host={$host};port={$port};dbname={$baza};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $haslo, $options);
} catch (PDOException $e) {
    // W środowisku developerskim pokaż błąd dla diagnostyki; w produkcji ustaw $debug = false
    $debug = true;
    if ($debug) {
        echo "Błąd połączenia z bazą danych: " . htmlspecialchars($e->getMessage());
    } else {
        echo "Błąd połączenia z bazą danych.";
    }
    exit;
}
?>
