/**
 * Premium Excel Importer V2 (Standalone Edition)
 * Handles all excel import logic independently to avoid conflicts.
 */
const excelImporter = {
    currentData: [],

    // 1. Open Modal
    openModal: function () {
        const modal = document.getElementById('excelImportModal');
        if (modal) {
            this.reset();
            modal.style.display = 'block';
        } else {
            alert('خطأ: نافذة الاستيراد غير موجودة في الصفحة!');
        }
    },

    // 2. Close Modal
    closeModal: function () {
        document.getElementById('excelImportModal').style.display = 'none';
    },

    // 3. Reset State
    reset: function () {
        this.currentData = [];
        const fileInput = document.getElementById('excelFileInput');
        if (fileInput) fileInput.value = '';
        
        document.getElementById('excelPreviewSection').style.display = 'none';
        document.getElementById('excelDropZone').style.display = 'flex';
        
        const showAllBtn = document.getElementById('showAllPreviewBtn');
        if (showAllBtn) showAllBtn.style.display = 'none';

        const btn = document.getElementById('confirmExcelImportBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = 'تأكيد وحفظ البيانات';
        }
    },

    // 4. Handle File Selection
    handleFile: function (file) {
        if (!file || !file.name.match(/\.(xlsx|xls)$/)) {
            showToast('الرجاء اختيار ملف Excel صحيح (.xlsx أو .xls)', 'error');
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                const jsonData = XLSX.utils.sheet_to_json(firstSheet);

                if (jsonData.length === 0) {
                    showToast('الملف المختار فارغ!', 'error');
                    return;
                }

                this.currentData = jsonData;
                this.renderPreview(jsonData);
            } catch (err) {
                console.error(err);
                showToast('حدث خطأ أثناء قراءة ملف Excel', 'error');
            }
        };
        reader.readAsArrayBuffer(file);
    },

    // 5. Render Live Preview
    renderPreview: function (data) {
        const previewSection = document.getElementById('excelPreviewSection');
        const headerRow = document.getElementById('excelPreviewHeader');
        const body = document.getElementById('excelPreviewBody');
        const countEl = document.getElementById('excelRowCount');
        const processBtn = document.getElementById('confirmExcelImportBtn');

        if (!data || data.length === 0) return;

        const headers = Object.keys(data[0]);
        headerRow.innerHTML = headers.map(h => `<th>${h}</th>`).join('');

        this.renderTableRows(data.slice(0, 10), headers);

        if (data.length > 10) {
            const showMoreBtn = document.getElementById('showAllPreviewBtn');
            if (showMoreBtn) showMoreBtn.style.display = 'inline-block';
        }

        countEl.innerText = data.length;
        previewSection.style.display = 'block';
        processBtn.disabled = false;
        document.getElementById('excelDropZone').style.display = 'none';
    },

    // Render helper for rows
    renderTableRows: function (rows, headers) {
        const body = document.getElementById('excelPreviewBody');
        body.innerHTML = rows.map(row => {
            const cells = headers.map(h => {
                let val = row[h] || '';
                // If it's a date column and the value is a number (Excel date), format it
                const dateCols = ['دخول المملكة', 'دخول الإيواء', 'تاريخ الدخول', 'تاريخ دخول الإيواء'];
                if (dateCols.includes(h) && typeof val === 'number') {
                    val = this.formatDate(val);
                }
                return `<td>${val}</td>`;
            }).join('');
            return `<tr>${cells}</tr>`;
        }).join('');
    },

    // Show all records in preview
    showFullPreview: function () {
        if (this.currentData.length === 0) return;
        const headers = Object.keys(this.currentData[0]);
        this.renderTableRows(this.currentData, headers);
        document.getElementById('showAllPreviewBtn').style.display = 'none';
    },

    // 6. Start Import to Server
    startImport: async function () {
        if (this.currentData.length === 0) return;

        const processBtn = document.getElementById('confirmExcelImportBtn');
        const isArchived = parseInt(document.getElementById('excelImportTarget').value);

        processBtn.disabled = true;
        processBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...';

        // Mapping Data based on your requested headers
        const mappedData = this.currentData.map(row => ({
            worker_name: row['العاملة'] || row['اسم العاملة'] || '',
            passport: row['الجواز'] || row['رقم الجواز'] || '',
            nationality: row['الجنسية'] || '',
            office: row['المكتب'] || '',
            customer: row['العميل'] || '',
            national_id: row['الهوية'] || '',
            guarantee_status: row['الضمان'] || 'داخل الضمان',
            housing_location: row['الموقع'] || 'الرياض',
            entry_date: this.formatDate(row['دخول المملكة'] || row['تاريخ الدخول']),
            housing_entry_date: this.formatDate(row['دخول الإيواء'] || row['تاريخ دخول الإيواء']),
            salary: row['الراتب'] || 0,
            status_description: row['شرح الحالة'] || '',
            action_type: row['الإجراء'] || 'السكن',
            ticket_info: row['التذكرة'] || '',
            settlement_status: row['التسوية'] || 'لم يتم الخصم',
            financial_notes: row['ملاحظات مالية'] || '',
            is_archived: isArchived
        }));

        try {
            const response = await fetch('api.php?action=bulk_add', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(mappedData)
            });
            const result = await response.json();

            if (result.success) {
                this.closeModal();
                showToast(`تم استيراد ${result.count} سجل بنجاح إلى ${isArchived ? 'الأرشيف' : 'الجدول الرئيسي'}!`, 'add');
                setTimeout(() => location.reload(), 2000); // Wait for toast
            } else {
                showToast('فشل الاستيراد: ' + result.message, 'error');
            }
        } catch (err) {
            showToast('حدث خطأ في الاتصال بالسيرفر', 'error');
            console.error(err);
        } finally {
            processBtn.disabled = false;
            processBtn.innerHTML = 'تأكيد وحفظ البيانات';
        }
    },

    // Helper: Format Excel Date
    formatDate: function (val) {
        if (!val) return null;
        if (typeof val === 'number') {
            const date = new Date((val - 25569) * 86400 * 1000);
            return date.toISOString().split('T')[0];
        }
        return val;
    },

    // 7. Download Sample File
    downloadSample: function () {
        const sampleData = [
            {
                "العاملة": "اسم العاملة هنا",
                "الجواز": "A0000000",
                "الجنسية": "الفلبين",
                "المكتب": "اسم المكتب",
                "العميل": "اسم العميل",
                "الهوية": "1000000000",
                "الضمان": "داخل الضمان",
                "الموقع": "الرياض",
                "دخول المملكة": "2023-01-01",
                "دخول الإيواء": "2023-01-05",
                "الراتب": 1500,
                "شرح الحالة": "وصف الحالة هنا",
                "الإجراء": "السكن",
                "التذكرة": "لا يوجد",
                "التسوية": "لم يتم الخصم",
                "ملاحظات مالية": "لا يوجد"
            }
        ];

        const worksheet = XLSX.utils.json_to_sheet(sampleData);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, "Sample");
        XLSX.writeFile(workbook, "Worker_Import_Sample.xlsx");
    }
};

// Initialize Drag & Drop events
document.addEventListener('DOMContentLoaded', () => {
    const dropZone = document.getElementById('excelDropZone');
    if (dropZone) {
        dropZone.ondragover = (e) => { e.preventDefault(); dropZone.style.borderColor = '#4361ee'; dropZone.style.background = '#f0f7ff'; };
        dropZone.ondragleave = () => { dropZone.style.borderColor = '#cbd5e1'; dropZone.style.background = '#f8fafc'; };
        dropZone.ondrop = (e) => {
            e.preventDefault();
            excelImporter.handleFile(e.dataTransfer.files[0]);
        };
    }
});
