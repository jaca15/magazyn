<?php
require 'auth.php';
require_login();
require 'polaczenie.php';

header('Content-Type: application/json');

$request = json_decode(file_get_contents('php://input'), true);

// Weryfikacja CSRF: sprawdź w JSON body, $_POST oraz nagłówku HTTP
$csrfSubmitted = $request['csrf_token']
    ?? $_POST['csrf_token']
    ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
$csrfExpected = $_SESSION['csrf_token'] ?? '';
if ($csrfExpected === '' || !hash_equals($csrfExpected, (string)$csrfSubmitted)) {
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowy token CSRF.']);
    exit;
}

$id = isset($request['id']) ? (int)$request['id'] : 0;

if ($id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Nieprawidłowe ID sprzętu.',
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM sprzet WHERE id = :id");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Sprzęt został pomyślnie usunięty.',
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Nie znaleziono sprzętu o podanym ID.',
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Błąd podczas próby usunięcia: ' . htmlspecialchars($e->getMessage()),
    ]);
}
exit;