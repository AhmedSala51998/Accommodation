<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة سكن العاملات</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- jQuery (Required for search and dynamic features) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SheetJS for Excel Parsing -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        /* Standalone Excel Modal Styles to avoid conflicts */
        .excel-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
        }

        .excel-modal-content {
            background: white;
            margin: 3% auto;
            padding: 25px;
            border-radius: 24px;
            width: 95%;
            max-width: 1100px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .excel-upload-zone {
            border: 3px dashed #cbd5e1;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .excel-upload-zone:hover {
            border-color: #4361ee;
            background: #f0f7ff;
        }

        .excel-preview-table-wrapper {
            margin-top: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: auto;
            max-height: 250px;
            background: white;
        }

        .excel-preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .excel-preview-table th {
            background: #f1f5f9;
            padding: 10px;
            border: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            font-weight: 800;
        }

        .excel-preview-table td { padding: 8px; border: 1px solid #e2e8f0; text-align: center; color: #475569; }

        /* Focus effect for office search */
        .search-box:focus-within {
            border-color: #4361ee !important;
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.15) !important;
            transform: translateY(-1px);
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <header>
            <div class="header-content">
                <h1>إدارة سكن العاملات</h1>
                <div class="header-actions">
                    <button id="manageOfficesBtn" class="btn btn-secondary"><i class="fas fa-building"></i> إدارة
                        المكاتب</button>
                    <!-- Excel Import Button Linked to standalone script -->
                    <button class="btn btn-success" onclick="excelImporter.openModal()"><i
                            class="fas fa-file-excel"></i> استيراد Excel</button>
                    <button id="bulkAddBtn" class="btn btn-primary"><i class="fas fa-plus-circle"></i> إضافة </button>
                    <button id="bulkEditBtn" class="btn btn-warning" disabled><i class="fas fa-edit"></i> تعديل
                        محدد</button>
                    <button id="bulkDeleteBtn" class="btn btn-danger" disabled><i class="fas fa-trash"></i> حذف
                        محدد</button>
                    <button id="bulkArchiveBtn" class="btn btn-secondary" disabled><i class="fas fa-archive"></i> أرشفة
                        المحدد</button>
                    <a href="archive.php" class="btn btn-success"><i class="fas fa-box-open"></i> الأرشيف</a>
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
                تقرير بيانات سكن العاملات</h1>
            <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-weight: bold;">
                <span>تاريخ التقرير:
                    <?php echo date('Y-m-d'); ?>
                </span>
                <span>الفرع: الإدارة العامة</span>
            </div>
        </div>

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
                        <th>الراتب</th>
                        <th>الإجراء</th>
                        <th>التذكرة</th>
                        <th>التسوية</th>
                        <th style="width: 80px;" class="no-print">تفاصيل</th>
                    </tr>
                </thead>
                <tbody id="workerBody"></tbody>
            </table>
        </main>
    </div>

    <!-- Standalone Excel Modal -->
    <div id="excelImportModal" class="excel-modal">
        <div class="excel-modal-content">
            <div
                style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:15px;">
                <h2 style="margin:0;"><i class="fas fa-file-excel" style="color:#10b981;"></i> استيراد ذكي من Excel</h2>
                <div style="display:flex; gap:10px;">
                    <button class="btn btn-secondary" style="padding:5px 15px; font-size:12px; background:#e2e8f0;"
                        onclick="excelImporter.downloadSample()">
                        <i class="fas fa-download"></i> تحميل ملف نموذجي (Sample)
                    </button>
                    <span style="font-size:30px; cursor:pointer;" onclick="excelImporter.closeModal()">&times;</span>
                </div>
            </div>

            <div
                style="background:#f1f5f9; padding:15px; border-radius:12px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
                <div style="font-weight:800; color:#4361ee;">هل تريد استيراد هذه البيانات إلى الأرشيف؟</div>
                <select id="excelImportTarget"
                    style="padding:10px 20px; border-radius:10px; border:1px solid #ddd; outline:none; font-weight:800; cursor:pointer;">
                    <option value="0">لا (إلى الجدول الرئيسي)</option>
                    <option value="1">نعم (إلى الأرشيف مباشرة)</option>
                </select>
            </div>

            <div id="excelDropZone" class="excel-upload-zone"
                onclick="document.getElementById('excelFileInput').click()">
                <i class="fas fa-cloud-upload-alt" style="font-size:60px; color:#4361ee;"></i>
                <p style="font-size:18px; font-weight:bold; margin-top:10px;">اسحب ملف الإكسيل هنا أو اضغط للاختيار</p>
                <input type="file" id="excelFileInput" accept=".xlsx, .xls" style="display:none;"
                    onchange="excelImporter.handleFile(this.files[0])">
            </div>

            <div id="excelPreviewSection" style="display:none; margin-top:20px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <p style="font-weight:800; color:#4361ee; margin:0;">معاينة البيانات (تم قراءة <span
                            id="excelRowCount">0</span> سجل):</p>
                    <div style="display:flex; gap:10px;">
                        <button id="showAllPreviewBtn" class="btn btn-secondary"
                            style="display:none; padding:5px 15px; font-size:12px;"
                            onclick="excelImporter.showFullPreview()">عرض كل السجلات</button>
                        <button class="btn btn-danger" style="padding:5px 15px; font-size:12px;"
                            onclick="excelImporter.reset()">حذف الملف ورفع غيره</button>
                    </div>
                </div>
                <div class="excel-preview-table-wrapper">
                    <table class="excel-preview-table">
                        <thead>
                            <tr id="excelPreviewHeader"></tr>
                        </thead>
                        <tbody id="excelPreviewBody"></tbody>
                    </table>
                </div>
            </div>

            <div
                style="margin-top:25px; border-top:1px solid #eee; padding-top:20px; display:flex; justify-content:flex-end; gap:10px;">
                <button class="btn btn-secondary" onclick="excelImporter.closeModal()">إلغاء</button>
                <button id="confirmExcelImportBtn" class="btn btn-primary" disabled
                    onclick="excelImporter.startImport()">تأكيد وحفظ البيانات</button>
            </div>
        </div>
    </div>

    <!-- Re-instating all original modals for app.js functionality -->
    <div id="addModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2>إضافة عاملات (نظام الصفين الذكي)</h2><span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="addBody" class="worker-group-list"></div>
                <div class="modal-actions"><button id="addRowBtn" style="margin-top:10px"
                        class="btn btn-secondary">إضافة عاملة جديدة</button></div>
            </div>
            <div class="modal-footer"><button id="saveBulkAdd" class="btn btn-primary">حفظ الكل</button></div>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content large">
            <div class="modal-header">
                <h2>تعديل بيانات العاملات المختارة</h2><span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="editBody" class="worker-group-list"></div>
            </div>
            <div class="modal-footer"><button id="saveBulkEdit" class="btn btn-warning">حفظ التعديلات</button></div>
        </div>
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

    <!-- Standalone Office Import Modal -->
    <div id="officeImportModal" class="excel-modal">
        <div class="excel-modal-content" style="max-width: 600px;">
            <div
                style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:15px;">
                <h2 style="margin:0;"><i class="fas fa-building" style="color:#10b981;"></i> استيراد مكاتب من Excel</h2>
                <div style="display:flex; gap:10px;">
                    <button class="btn btn-secondary" style="padding:5px 15px; font-size:12px; background:#e2e8f0;"
                        onclick="excelImporter.downloadOfficeSample()">
                        <i class="fas fa-download"></i> نموذج المكاتب
                    </button>
                    <span style="font-size:30px; cursor:pointer;"
                        onclick="excelImporter.closeOfficeModal()">&times;</span>
                </div>
            </div>

            <div id="officeDropZone" class="excel-upload-zone"
                onclick="document.getElementById('officeExcelFileInput').click()">
                <i class="fas fa-cloud-upload-alt" style="font-size:60px; color:#4361ee;"></i>
                <p style="font-size:16px; font-weight:bold; margin-top:10px;">اسحب ملف المكاتب هنا أو اضغط للاختيار</p>
                <input type="file" id="officeExcelFileInput" accept=".xlsx, .xls" style="display:none;"
                    onchange="excelImporter.handleOfficeFile(this.files[0])">
            </div>

            <div id="officePreviewSection" style="display:none; margin-top:20px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <p style="font-weight:800; color:#4361ee; margin:0;">معاينة المكاتب (<span
                            id="officeRowCount">0</span> مكتب):</p>
                    <button class="btn btn-danger" style="padding:5px 15px; font-size:12px;"
                        onclick="excelImporter.resetOfficeImport()">حذف</button>
                </div>
                <div class="excel-preview-table-wrapper">
                    <table class="excel-preview-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم المكتب</th>
                            </tr>
                        </thead>
                        <tbody id="officePreviewBody"></tbody>
                    </table>
                </div>
            </div>

            <div
                style="margin-top:25px; border-top:1px solid #eee; padding-top:20px; display:flex; justify-content:flex-end; gap:10px;">
                <button class="btn btn-secondary" onclick="excelImporter.closeOfficeModal()">إلغاء</button>
                <button id="confirmOfficeImportBtn" class="btn btn-primary" disabled
                    onclick="excelImporter.startOfficeImport()">تأكيد وحفظ المكاتب</button>
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
                <div class="modal-actions"><button id="addOfficeInputBtn" class="btn btn-secondary">إضافة حقل مكتب
                        آخر</button></div>
            </div>
            <div class="modal-footer"><button id="saveOfficesBtn" class="btn btn-primary">حفظ الكل</button></div>
        </div>
    </div>

    <div id="detailModal" class="modal">
        <div class="modal-content detail-modal-content">
            <div class="modal-header">
                <h2 id="detailTitle">تفاصيل</h2><span class="close"
                    onclick="document.getElementById('detailModal').style.display='none'">&times;</span>
            </div>
            <div class="modal-body">
                <p id="detailContent"></p>
            </div>
            <div class="modal-footer"><button class="btn btn-secondary"
                    onclick="document.getElementById('detailModal').style.display='none'">إغلاق</button></div>
        </div>
    </div>

    <div id="toastContainer" class="toast-container"></div>
    <script>const PAGE_TYPE = 'main';</script>
    <script src="assets/js/app.js"></script>
    <!-- Standalone Excel Script (Force Refresh) -->
    <script src="assets/js/excel_importer.js?v=1.4"></script>
</body>

</html>