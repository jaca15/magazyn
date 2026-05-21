<?php
// polaczenie.php - połączenie z bazą danych (TCP na porcie 3307)
// Dostosuj user/haslo jeśli potrzeba
$host = '127.0.0.1';   // użyj 127.0.0.1 zamiast 'localhost' aby wymusić TCP
$port = 3307;         // ustawiony port
$baza = '';
$user = '';
$haslo = ''; // ustaw hasło
$charset = 'utf8mb4';

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
