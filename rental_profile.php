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
            <th>الإجراءات</th>
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

                <?php
                $isReturned =
                    !empty($row['returned_date']) &&
                    $row['returned_date'] !== '0000-00-00';
                ?>

                <?php if($isReturned): ?>

                    <span class="badge-returned">
                        عادت
                    </span>

                <?php else: ?>

                    <span class="badge-active">
                        قيد التأجير
                    </span>

                <?php endif; ?>

            </td>

            <td>

                <?php if(!$isReturned): ?>

                    <button
                        class="btn btn-sm btn-warning edit-return-btn"
                        data-rental-id="<?= $row['id'] ?>"
                        data-returned-date="<?= $row['returned_date'] ?>"
                        title="تحديث تاريخ العودة">

                        <i class="fas fa-edit"></i>

                    </button>

                <?php else: ?>

                    <button
                        class="btn btn-sm btn-success"
                        disabled>

                        <i class="fas fa-check"></i>

                    </button>

                <?php endif; ?>

            </td>

        </tr>

    <?php endforeach; ?>

    <div class="modal fade" id="returnDateModal">

        <div class="modal-dialog">

            <div class="modal-content">

                <div class="modal-header bg-warning text-dark">

                    <h5 class="modal-title">

                        <i class="fas fa-calendar-check"></i>

                        تحديث تاريخ العودة

                    </h5>

                </div>

                <div class="modal-body">

                    <input type="hidden" id="rental_id">

                    <label class="mb-2">
                        تاريخ العودة
                    </label>

                    <input type="date"
                        id="new_returned_date"
                        class="form-control">

                </div>

                <div class="modal-footer">

                    <button
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                        إغلاق

                    </button>

                    <button
                        class="btn btn-warning"
                        id="saveReturnedDate">

                        <i class="fas fa-save"></i>

                        حفظ

                    </button>

                </div>

            </div>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).on('click','.edit-return-btn',function(){

            $('#rental_id').val(
                $(this).data('rental-id')
            );

            $('#new_returned_date').val(
                $(this).data('returned-date')
            );

            new bootstrap.Modal(
                document.getElementById('returnDateModal')
            ).show();

        });

        $(document).on('click','#saveReturnedDate',function(){

            $.ajax({

                url:'update_returned_date.php',

                type:'POST',

                dataType:'json',

                data:{
                    rental_id:$('#rental_id').val(),
                    returned_date:$('#new_returned_date').val()
                },

                success:function(res){

                    if(res.success){

                        var modalEl = document.getElementById('returnDateModal');

                        var modal =
                            bootstrap.Modal.getInstance(modalEl) ||
                            new bootstrap.Modal(modalEl);

                        modal.hide();

                        Swal.fire({
                            icon:'success',
                            title:'تم التحديث',
                            text:'تم تحديث تاريخ العودة',
                            timer:1500,
                            showConfirmButton:false
                        });

                        setTimeout(function(){

                            openRentalProfile(
                                $('#worker_id').val()
                            );

                        },500);

                    }

                }

            });

        });
    </script>

    </tbody>

</table>

</div>

<?php endif; ?>