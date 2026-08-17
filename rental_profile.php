<?php

require 'config.php';

$stmt = $pdo->prepare("
SELECT *
FROM worker_rentals
WHERE worker_id=?
ORDER BY id DESC
");

$stmt->execute([
$_GET['worker_id']
]);

while($row=$stmt->fetch()){

echo '
<tr>
<td>'.$row['renter_name'].'</td>
<td>'.$row['renter_phone'].'</td>
<td>'.$row['departure_date'].'</td>
<td>'.$row['rent_start_date'].'</td>
<td>'.$row['rent_end_date'].'</td>
<td>'.$row['returned_date'].'</td>
</tr>';
}