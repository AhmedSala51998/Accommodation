<?php

require 'config.php';

$stmt = $pdo->prepare("
UPDATE worker_rentals
SET returned_date=?
WHERE id=?
");

$stmt->execute([
    $_POST['returned_date'],
    $_POST['rental_id']
]);

echo json_encode([
    'success' => true
]);