<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['Account_ID']) || ($_SESSION['Account_Type'] ?? '') !== 'PA') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/config/database.php';

$foodBankId = (int) ($_POST['foodbank_id'] ?? 0);
if ($foodBankId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid food bank.']);
    exit;
}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS PA_FOOD_BANK_FAVORITES (
            Favorite_ID INT AUTO_INCREMENT PRIMARY KEY,
            Account_ID INT NOT NULL,
            FoodBank_ID INT NOT NULL,
            Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_pa_foodbank_favorite (Account_ID, FoodBank_ID),
            INDEX idx_pa_favorite_account (Account_ID),
            INDEX idx_pa_favorite_foodbank (FoodBank_ID),
            CONSTRAINT fk_pa_favorite_account
                FOREIGN KEY (Account_ID) REFERENCES ACCOUNTS(Account_ID)
                ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_pa_favorite_foodbank
                FOREIGN KEY (FoodBank_ID) REFERENCES FOOD_BANKS(FoodBank_ID)
                ON DELETE CASCADE ON UPDATE CASCADE
        )
    ");

    $stmt = $pdo->prepare("
        SELECT Favorite_ID
        FROM PA_FOOD_BANK_FAVORITES
        WHERE Account_ID = ?
          AND FoodBank_ID = ?
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['Account_ID'], $foodBankId]);
    $favoriteId = $stmt->fetchColumn();

    if ($favoriteId) {
        $delete = $pdo->prepare("DELETE FROM PA_FOOD_BANK_FAVORITES WHERE Favorite_ID = ?");
        $delete->execute([$favoriteId]);
        echo json_encode(['success' => true, 'favorited' => false]);
        exit;
    }

    $insert = $pdo->prepare("
        INSERT INTO PA_FOOD_BANK_FAVORITES (Account_ID, FoodBank_ID)
        VALUES (?, ?)
    ");
    $insert->execute([$_SESSION['Account_ID'], $foodBankId]);

    echo json_encode(['success' => true, 'favorited' => true]);
} catch (PDOException $e) {
    error_log('Toggle favorite food bank error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to update favorite.']);
}
