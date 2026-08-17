<?php

require 'config.php';

try {

    $stmt = $pdo->prepare("
        INSERT INTO worker_rentals
        (
            worker_id,
            renter_name,
            renter_phone,
            departure_date,
            rent_start_date,
            rent_end_date,
            returned_date,
            notes
        )
        VALUES
        (
            ?,?,?,?,?,?,?,?
        )
    ");

    $stmt->execute([
        $_POST['worker_id'],
        $_POST['renter_name'],
        $_POST['renter_phone'],
        $_POST['departure_date'],
        $_POST['rent_start_date'],
        $_POST['rent_end_date'],
        $_POST['returned_date'],
        $_POST['notes']
    ]);

    echo json_encode([
        'success' => true
    ]);

} catch(Exception $e){

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

}