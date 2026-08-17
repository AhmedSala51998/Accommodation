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

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalRentals = count($rows);

$activeRentals = 0;

foreach($rows as $r){

    if(
        !empty($r['rent_start_date']) &&
        empty($r['returned_date'])
    ){
        $activeRentals++;
    }
}
?>

<style>
.rental-stats{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
    gap:15px;
    margin-bottom:25px;
}

.rental-card{
    background:#fff;
    border-radius:18px;
    padding:20px;
    box-shadow:0 5px 20px rgba(0,0,0,.06);
    border:1px solid #eee;
}

.rental-card .title{
    color:#777;
    font-size:14px;
}

.rental-card .value{
    font-size:30px;
    font-weight:700;
    margin-top:8px;
}

.rental-card.orange{
    border-right:5px solid #ff9800;
}

.rental-card.green{
    border-right:5px solid #22c55e;
}

.rental-table{
    width:100%;
    border-collapse:collapse;
    overflow:hidden;
    border-radius:18px;
}

.rental-table thead th{
    background:#ff9800;
    color:#fff;
    padding:14px;
    text-align:center;
    font-size:14px;
}

.rental-table tbody td{
    padding:12px;
    text-align:center;
    border-bottom:1px solid #eee;
}

.rental-table tbody tr:hover{
    background:#fafafa;
}

.badge-active{
    background:#dcfce7;
    color:#166534;
    padding:6px 12px;
    border-radius:30px;
    font-size:12px;
    font-weight:700;
}

.badge-returned{
    background:#fee2e2;
    color:#991b1b;
    padding:6px 12px;
    border-radius:30px;
    font-size:12px;
    font-weight:700;
}

.empty-box{
    text-align:center;
    padding:50px;
    background:#fff;
    border-radius:20px;
}

.empty-box i{
    font-size:60px;
    color:#ccc;
    margin-bottom:15px;
}
</style>

<div class="rental-stats">

    <div class="rental-card orange">
        <div class="title">إجمالي عمليات التأجير</div>
        <div class="value"><?= $totalRentals ?></div>
    </div>

    <div class="rental-card green">
        <div class="title">التأجير النشط</div>
        <div class="value"><?= $activeRentals ?></div>
    </div>

</div>

<?php if(empty($rows)): ?>

<div class="empty-box">

    <i class="fas fa-folder-open"></i>

    <h4>لا توجد بيانات تأجير</h4>

    <p>لم يتم تسجيل أي عملية تأجير لهذه العاملة.</p>

</div>

<?php else: ?>

<div class="table-responsive">

<table class="rental-table">

    <thead>

        <tr>
            <th>#</th>
            <th>المستأجر</th>
            <th>الجوال</th>
            <th>تاريخ الخروج</th>
            <th>بداية التأجير</th>
            <th>نهاية التأجير</th>
            <th>تاريخ العودة</th>
            <th>الحالة</th>
        </tr>

    </thead>

    <tbody>

    <?php
    $i = 1;

    foreach($rows as $row):
    ?>

        <tr>

            <td><?= $i++ ?></td>

            <td><?= htmlspecialchars($row['renter_name']) ?></td>

            <td><?= htmlspecialchars($row['renter_phone']) ?></td>

            <td><?= $row['departure_date'] ?></td>

            <td><?= $row['rent_start_date'] ?></td>

            <td><?= $row['rent_end_date'] ?></td>

            <td>
                <?= $row['returned_date'] ?: '-' ?>
            </td>

            <td>

                <?php if(!empty($row['returned_date'])): ?>

                    <span class="badge-returned">
                        عادت
                    </span>

                <?php else: ?>

                    <span class="badge-active">
                        قيد التأجير
                    </span>

                <?php endif; ?>

            </td>

        </tr>

    <?php endforeach; ?>

    </tbody>

</table>

</div>

<?php endif; ?>