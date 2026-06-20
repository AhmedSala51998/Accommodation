<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير إداري</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=1.6">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        /* keep small page-specific styles if needed */
    </style>
</head>

<body>
    <div class="dashboard-container">
        <header>
            <div class="header-content" style="flex-direction: column; align-items: stretch; gap: 0;">
                <div class="header-row-1">
                    <h1>تقرير إداري</h1>
                    <div class="header-nav">
                        <a href="index.php" class="btn btn-success"><i class="fas fa-home"></i> الرئيسية</a>
                        <a href="all_workers.php" class="btn btn-success" style="background: #3f37c9; color: white; border-color: #3f37c9;"><i class="fas fa-users"></i> تقرير شامل</a>
                    </div>
                </div>

            </div>
        </header>

        <section class="filters-section">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="globalSearch" placeholder="بحث شامل في جميع الأعمدة...">
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
                        <!-- new columns after In Housing -->
                        <th>رقم الجوال</th>
                        <th>جهة الاستلام</th>
                        <th>جهة الاستلام (اخرى)</th>
                        <th>الجواز مفقود</th>
                        <th>ملاحظة فقدان الجواز</th>
                        <th>الحالة</th>
                        <th style="width: 80px;" class="no-print">تفاصيل</th>
                    </tr>
                </thead>
                <tbody id="workerBody"></tbody>
            </table>
        </main>
    </div>

    <div id="officesModal" class="modal">
        <div class="modal-content large" style="max-width: 1000px;">
            <div class="modal-header">
                <h2><i class="fas fa-building"></i> إدارة المكاتب</h2><span class="close">&times;</span>
            </div>
            <div class="modal-body" style="padding: 20px 30px;">
                <div style="padding: 0 10px; margin-bottom: 25px;">
                    <div class="header-actions"
                        style="display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; background: #f1f5f9; border-radius: 12px; gap: 15px; border: 1px solid #e2e8f0; flex-wrap: nowrap; width: 100%; margin: 0;">
                        
                        <div class="search-box" style="flex: 1; min-width: 180px; margin: 0; background: white; border: 2px solid #cbd5e1; border-radius: 10px; height: 40px; position: relative;">
                            <i class="fas fa-search" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 14px;"></i>
                            <input type="text" id="officeSearchInput" placeholder="بحث عن مكتب..." style="border: none; outline: none; padding: 0 45px 0 15px; width: 100%; height: 100%; font-size: 13px; font-weight: 600; color: #1e293b; background: transparent; text-align: right;">
                        </div>

                        <button id="showAddOfficesBtn" class="btn btn-primary" style="height: 42px; padding: 0 15px; font-size: 13px; white-space: nowrap;"><i class="fas fa-plus"></i> إضافة</button>
                        <button class="btn btn-success" onclick="excelImporter.openOfficeModal()" style="height: 42px; padding: 0 15px; font-size: 13px; white-space: nowrap;"><i class="fas fa-file-excel"></i> استيراد</button>
                        <button id="bulkEditOfficesBtn" class="btn btn-warning" disabled style="height: 42px; padding: 0 15px; font-size: 13px; white-space: nowrap;"><i class="fas fa-edit"></i> تعديل</button>
                        <button id="bulkDeleteOfficesBtn" class="btn btn-danger" disabled style="height: 42px; padding: 0 15px; font-size: 13px; white-space: nowrap;"><i class="fas fa-trash"></i> حذف</button>
                        
                        <div class="range-selector" style="display: flex; align-items: center; gap: 5px; background: white; padding: 2px 8px; border-radius: 8px; border: 1px solid #cbd5e1; margin-right: 5px;">
                            <span style="font-size: 11px; font-weight: bold; color: #64748b;">من:</span>
                            <input type="number" id="officeRangeFrom" min="1" style="width: 40px; height: 28px; border-radius: 5px; border: 1px solid #cbd5e1; text-align: center; font-size: 11px;">
                            <span style="font-size: 11px; font-weight: bold; color: #64748b;">إلى:</span>
                            <input type="number" id="officeRangeTo" min="1" style="width: 40px; height: 28px; border-radius: 5px; border: 1px solid #cbd5e1; text-align: center; font-size: 11px;">
                        </div>

                        <div style="display: flex; align-items: center; gap: 5px; padding-right: 10px; border-right: 2px solid #e2e8f0; height: 30px;">
                            <input type="checkbox" id="selectAllOffices" style="width: 16px; height: 16px; cursor: pointer;">
                            <label for="selectAllOffices" style="font-weight: bold; cursor: pointer; font-size: 12px; white-space: nowrap;">الكل</label>
                        </div>
                    </div>
                </div>
                <div id="officesBody" class="offices-grid"></div>
            </div>
        </div>
    </div>

    <div id="addOfficesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="officeModalTitle">إضافة مكاتب جديدة</h2><span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="officeInputsContainer" class="worker-group-list"></div>
                <div class="modal-actions"><button id="addOfficeInputBtn" class="btn btn-secondary">إضافة حقل مكتب آخر</button></div>
            </div>
            <div class="modal-footer"><button id="saveOfficesBtn" class="btn btn-primary">حفظ الكل</button></div>
        </div>
    </div>

    <div id="detailModal" class="modal">
        <div class="modal-content detail-modal-content">
            <div class="modal-header">
                <h2 id="detailTitle">تفاصيل</h2><span class="close" onclick="document.getElementById('detailModal').style.display='none'">&times;</span>
            </div>
            <div class="modal-body">
                <p id="detailContent"></p>
            </div>
            <div class="modal-footer"><button class="btn btn-secondary" onclick="document.getElementById('detailModal').style.display='none'">إغلاق</button></div>
        </div>
    </div>

    <div id="toastContainer" class="toast-container"></div>
    <script>const PAGE_TYPE = 'admin_report';</script>
    <script src="assets/js/app.js?v=1.6"></script>
    <script src="assets/js/excel_importer.js?v=1.4"></script>
</body>

</html>
