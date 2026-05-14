<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أرشيف العاملات</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- jQuery (Required for archive data and features) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <div class="dashboard-container">
        <header style="background: linear-gradient(135deg, #64748b 0%, #334155 100%);">
            <div class="header-content">
                <h1>أرشيف العاملات</h1>
                <div class="header-actions">
                    <button id="bulkEditBtn" class="btn btn-warning" disabled><i class="fas fa-edit"></i> تعديل
                        محدد</button>
                    <button id="bulkUnarchiveBtn" class="btn btn-primary" disabled><i class="fas fa-undo"></i> إلغاء
                        أرشفة المحدد</button>
                    <button id="bulkDeleteBtn" class="btn btn-danger" disabled><i class="fas fa-trash"></i> حذف
                        نهائي</button>
                    <a href="index.php" class="btn btn-success"><i class="fas fa-arrow-right"></i> العودة للرئيسية</a>
                    <div class="range-selector" style="display: flex; align-items: center; gap: 4px; background: rgba(255,255,255,0.15); padding: 4px 8px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.2);">
                        <span style="font-size: 11px; font-weight: bold; color: white;">من:</span>
                        <input type="number" id="rangeFrom" min="1" style="width: 45px; height: 28px; border-radius: 6px; border: none; text-align: center; font-size: 12px; font-weight: bold;">
                        <span style="font-size: 11px; font-weight: bold; color: white;">إلى:</span>
                        <input type="number" id="rangeTo" min="1" style="width: 45px; height: 28px; border-radius: 6px; border: none; text-align: center; font-size: 12px; font-weight: bold;">
                        <button id="applyRangeSelect" style="display:none;"></button>
                    </div>
                    <button id="exportPdfBtn" class="btn btn-success"><i class="fas fa-file-pdf"></i> تصدير PDF</button>
                </div>
            </div>
        </header>

        <div class="print-header" style="display: none;">
            <h1 style="text-align: center; margin-bottom: 20px; border-bottom: 3px solid #000; padding-bottom: 10px;">
                تقرير أرشيف سكن العاملات</h1>
            <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-weight: bold;">
                <span>تاريخ التقرير:
                    <?php echo date('Y-m-d'); ?>
                </span>
                <span>النوع: بيانات مؤرشفة</span>
            </div>
        </div>

        <section class="filters-section">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="globalSearch" placeholder="بحث في الأرشيف...">
            </div>
            <div class="filter-grid">
                <select id="filterGuarantee">
                    <option value="">كل حالات الضمان</option>
                    <option value="داخل الضمان">داخل الضمان</option>
                    <option value="خارج الضمان">خارج الضمان</option>
                </select>
                <select id="filterNationality">
                    <option value="">كل الجنسيات</option>
                    <option value="اثيوبيا">اثيوبيا</option>
                    <option value="بوروندي">بوروندي</option>
                    <option value="الفلبين">الفلبين</option>
                    <option value="سريلانكا">سريلانكا</option>
                    <option value="اوغندا">اوغندا</option>
                    <option value="كينيا">كينيا</option>
                    <option value="الهند">الهند</option>
                    <option value="بنجلاديش">بنجلاديش</option>
                </select>
                <select id="filterHousing">
                    <option value="">كل المواقع</option>
                    <option value="الرياض">الرياض</option>
                    <option value="جدة">جدة</option>
                    <option value="ينبع">ينبع</option>
                </select>
                <select id="filterOffice">
                    <option value="">كل المكاتب</option>
                    <!-- Populated via JS -->
                </select>
                <select id="filterAction">
                    <option value="">كل الإجراءات</option>
                    <option value="السكن">السكن</option>
                    <option value="نقل خدمات">نقل خدمات</option>
                    <option value="خروج نهائي">خروج نهائي</option>
                    <option value="هروب">هروب</option>
                    <option value="اخرى">اخرى</option>
                </select>
                <select id="filterSettlement">
                    <option value="">كل حالات التسوية</option>
                    <option value="لم يتم الخصم">لم يتم الخصم</option>
                    <option value="تم الخصم">تم الخصم</option>
                    <option value="تم الخصم جزئياً">تم الخصم جزئياً</option>
                    <option value="لا تخصم">لا تخصم</option>
                </select>
            </div>
        </section>

        <main class="table-responsive">
            <table id="workerTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th style="width: 40px;">#</th>
                        <th>العاملة</th>
                        <th>الجواز</th>
                        <th>الجنسية</th>
                        <th>المكتب</th>
                        <th>العميل</th>
                        <th>الهوية</th>
                        <th>الضمان</th>
                        <th>الموقع</th>
                        <th>دخول المملكة</th>
                        <th>(In KSA)</th>
                        <th>دخول الإيواء</th>
                        <th>(In Housing)</th>
                        <th>الراتب</th>
                        <th>الإجراء</th>
                        <th>التذكرة</th>
                        <th>التسوية</th>
                        <th style="width: 80px;" class="no-print">تفاصيل</th>
                    </tr>
                </thead>
                <tbody id="workerBody">
                    <!-- Data loaded via JS -->
                </tbody>
            </table>
        </main>
    </div>

    <!-- Bulk Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2>تعديل بيانات العاملات المؤرشفة</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="editBody" class="worker-group-list"></div>
            </div>
            <div class="modal-footer">
                <button id="saveBulkEdit" class="btn btn-warning">حفظ التعديلات</button>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div id="detailModal" class="modal">
        <div class="modal-content detail-modal-content">
            <div class="modal-header">
                <h2 id="detailTitle">تفاصيل</h2>
                <span class="close" onclick="document.getElementById('detailModal').style.display='none'">&times;</span>
            </div>
            <div class="modal-body">
                <p id="detailContent"></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary"
                    onclick="document.getElementById('detailModal').style.display='none'">إغلاق</button>
            </div>
        </div>
    </div>

    <div id="toastContainer" class="toast-container"></div>
    <script>const PAGE_TYPE = 'archive';</script>
    <script src="assets/js/app.js"></script>
</body>

</html>