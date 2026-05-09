<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة العاملات - لوحة التحكم</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- jsPDF & autoTable for PDF Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <header>
            <div class="header-content">
                <h1>إدارة سكن العاملات</h1>
                <div class="header-actions">
                    <button id="bulkAddBtn" class="btn btn-primary"><i class="fas fa-plus-circle"></i> إضافة متعدة</button>
                    <button id="bulkEditBtn" class="btn btn-warning" disabled><i class="fas fa-edit"></i> تعديل محدد</button>
                    <button id="bulkDeleteBtn" class="btn btn-danger" disabled><i class="fas fa-trash"></i> حذف محدد</button>
                    <button id="exportPdfBtn" class="btn btn-success"><i class="fas fa-file-pdf"></i> تصدير PDF</button>
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
                <select id="filterHousing">
                    <option value="">كل مواقع الإيواء</option>
                    <option value="ايواء ينبع">ايواء ينبع</option>
                    <option value="ايواء جدة">ايواء جدة</option>
                    <option value="ايواء الرياض">ايواء الرياض</option>
                </select>
                <select id="filterAction">
                    <option value="">كل الإجراءات</option>
                    <option value="السكن">السكن</option>
                    <option value="نقل خدمات">نقل خدمات</option>
                    <option value="خروج نهائي">خروج نهائي</option>
                    <option value="اخرى">اخرى</option>
                </select>
                <select id="filterSettlement">
                    <option value="">كل حالات التسوية</option>
                    <option value="لم يتم الخصم">لم يتم الخصم</option>
                    <option value="تم الخصم">تم الخصم</option>
                    <option value="تم الخصم جزئياً">تم الخصم جزئياً</option>
                </select>
            </div>
        </section>

        <main class="table-responsive">
            <table id="workerTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>العاملة</th>
                        <th>الجواز</th>
                        <th>الجنسية</th>
                        <th>المكتب</th>
                        <th>العميل</th>
                        <th>الهوية</th>
                        <th>حالة الضمان</th>
                        <th>الإيواء</th>
                        <th>دخول المملكة</th>
                        <th>أيام (KSA)</th>
                        <th>دخول الإيواء</th>
                        <th>أيام (Housing)</th>
                        <th>الراتب</th>
                        <th>الإجراء</th>
                        <th>التذكرة</th>
                        <th>التسوية</th>
                    </tr>
                </thead>
                <tbody id="workerBody">
                    <!-- Data will be loaded via AJAX -->
                </tbody>
            </table>
        </main>
    </div>

    <!-- Modals -->
    <!-- Bulk Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2>إضافة متعدة للعاملات (أدخل جميع البيانات)</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="addTable" class="compact-table">
                        <thead>
                            <tr>
                                <th style="min-width: 280px;">العاملة</th>
                                <th style="min-width: 180px;">الجواز</th>
                                <th style="min-width: 180px;">الجنسية</th>
                                <th style="min-width: 200px;">المكتب</th>
                                <th style="min-width: 200px;">العميل</th>
                                <th style="min-width: 180px;">الهوية</th>
                                <th style="min-width: 200px;">حالة الضمان</th>
                                <th style="min-width: 200px;">الإيواء</th>
                                <th style="min-width: 200px;">دخول المملكة</th>
                                <th style="min-width: 200px;">دخول الإيواء</th>
                                <th style="min-width: 150px;">الراتب</th>
                                <th style="min-width: 250px;">شرح الحالة</th>
                                <th style="min-width: 200px;">الإجراء</th>
                                <th style="min-width: 180px;">التذكرة</th>
                                <th style="min-width: 200px;">التسوية</th>
                                <th style="min-width: 300px;">ملاحظات</th>
                                <th>حذف</th>
                            </tr>
                        </thead>
                        <tbody id="addBody">
                            <!-- New rows will be added here -->
                        </tbody>
                    </table>
                </div>
                <div class="modal-actions">
                    <button id="addRowBtn" class="btn btn-secondary"><i class="fas fa-plus"></i> إضافة صف جديد</button>
                </div>
            </div>
            <div class="modal-footer">
                <button id="saveBulkAdd" class="btn btn-primary">حفظ الكل</button>
            </div>
        </div>
    </div>

    <!-- Bulk Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2>تعديل البيانات المختارة (تعديل صفوف)</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="editTable" class="compact-table">
                        <thead>
                            <tr>
                                <th style="min-width: 280px;">العاملة</th>
                                <th style="min-width: 180px;">الجواز</th>
                                <th style="min-width: 180px;">الجنسية</th>
                                <th style="min-width: 200px;">المكتب</th>
                                <th style="min-width: 200px;">العميل</th>
                                <th style="min-width: 180px;">الهوية</th>
                                <th style="min-width: 200px;">حالة الضمان</th>
                                <th style="min-width: 200px;">الإيواء</th>
                                <th style="min-width: 200px;">دخول المملكة</th>
                                <th style="min-width: 200px;">دخول الإيواء</th>
                                <th style="min-width: 150px;">الراتب</th>
                                <th style="min-width: 250px;">شرح الحالة</th>
                                <th style="min-width: 200px;">الإجراء</th>
                                <th style="min-width: 180px;">التذكرة</th>
                                <th style="min-width: 200px;">التسوية</th>
                                <th style="min-width: 300px;">ملاحظات</th>
                            </tr>
                        </thead>
                        <tbody id="editBody">
                            <!-- Selected rows for editing will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button id="saveBulkEdit" class="btn btn-warning">حفظ جميع التعديلات</button>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="toast-container"></div>

    <script src="assets/js/app.js"></script>
</body>
</html>
