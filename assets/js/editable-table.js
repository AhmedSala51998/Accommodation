// دالة لتحويل خلية إلى عنصر قابل للتعديل
function makeCellEditable(cell, fieldName, fieldType, currentValue, options = [], rowId) {
    // حفظ القيمة الأصلية للرجوع إليها إذا لزم
    const originalValue = currentValue;

    // إنشاء عنصر التعديل
    let editElement;

    switch(fieldType) {
        case 'text':
            editElement = `<input type="text" value="${currentValue || ''}" class="cell-edit-input" style="width: 100%; border: none; background: transparent; padding: 4px; text-align: inherit;">`;
            break;
        case 'number':
            editElement = `<input type="number" value="${currentValue || 0}" class="cell-edit-input" style="width: 100%; border: none; background: transparent; padding: 4px; text-align: inherit;">`;
            break;
        case 'date':
            editElement = `<input type="date" value="${currentValue || ''}" class="cell-edit-input" style="width: 100%; border: none; background: transparent; padding: 4px;">`;
            break;
        case 'select':
            const optionsHtml = options.map(opt => `<option value="${opt.value}" ${opt.value === currentValue ? 'selected' : ''}>${opt.label}</option>`).join('');
            editElement = `<select class="cell-edit-select" style="width: 100%; border: none; background: transparent; padding: 4px;">${optionsHtml}</select>`;
            break;
        case 'badge':
            // للحقول التي تحتوي على badg مثل حالة الضمان
            const badgeOptions = options.map(opt => `<option value="${opt.value}" ${opt.value === currentValue ? 'selected' : ''}>${opt.label}</option>`).join('');
            editElement = `<select class="cell-edit-select" style="width: 100%; border: none; background: transparent; padding: 4px;">${badgeOptions}</select>`;
            break;
        default:
            editElement = `<input type="text" value="${currentValue || ''}" class="cell-edit-input" style="width: 100%; border: none; background: transparent; padding: 4px; text-align: inherit;">`;
    }

    // تغيير محتوى الخلية إلى عنصر التعديل
    cell.innerHTML = editElement;

    // إعادة تفعيل النقرات على العنصر الجديد
    const newElement = cell.querySelector('.cell-edit-input, .cell-edit-select');
    if (newElement) {
        // التركيز على العنصر عند الإنشاء
        setTimeout(() => newElement.focus(), 10);

        // حفظ التغييرات عند فقدان التركيز
        newElement.addEventListener('blur', () => {
            saveCellEdit(cell, fieldName, newElement.value, rowId, originalValue);
        });

        // حفظ التغييرات عند الضغط على Enter
        newElement.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                saveCellEdit(cell, fieldName, newElement.value, rowId, originalValue);
            }
        });

        // إلغاء التعديل عند الضغط على Escape
        newElement.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                cell.innerHTML = originalValue;
            }
        });
    }
}

// دالة لحفظ تعديل خلية
function saveCellEdit(cell, fieldName, newValue, rowId, originalValue) {
    // إذا لم يتغير القيمة، لا تفعل شيئًا
    if (newValue === originalValue) {
        cell.innerHTML = originalValue;
        return;
    }

    // إظهار مؤشر التحميل
    cell.innerHTML = `<div class="cell-loading"><i class="fas fa-spinner fa-spin"></i></div>`;

    // إرسال التغييرات إلى الخادم
    fetch('api_update_field.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: rowId,
            field: fieldName,
            value: newValue
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // تحديث قيمة الخلية
            if (fieldName === 'salary') {
                cell.innerHTML = parseFloat(newValue).toLocaleString('en-US', { minimumFractionDigits: 2 });
            } else if (fieldName === 'guarantee_status') {
                // للحقول التي تحتوي على badg
                cell.innerHTML = `<span class="badge ${newValue === 'داخل الضمان' ? 'badge-success' : 'badge-danger'}">${newValue}</span>`;
            } else {
                cell.innerHTML = newValue;
            }
            showToast('تم تحديث البيانات بنجاح', 'success');
        } else {
            showToast('حدث خطأ أثناء التحديث: ' + data.message, 'error');
            cell.innerHTML = originalValue;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('حدث خطأ أثناء الاتصال بالخادم', 'error');
        cell.innerHTML = originalValue;
    });
}

// دالة لعرض رسائل الإعلام
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('show');
    }, 10);

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}

// دالة لتحويل التاريخ من صيغة YYYY-MM-DD إلى صيغة DD/MM/YYYY
function formatDisplayDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB');
}

// دالة لتحويل التاريخ من صيغة DD/MM/YYYY إلى صيغة YYYY-MM-DD
function formatInputDate(dateString) {
    if (!dateString) return '';
    const parts = dateString.split('/');
    if (parts.length !== 3) return dateString;
    return `${parts[2]}-${parts[1]}-${parts[0]}`;
}

// دالة لتحويل الرقم إلى صيغة معقدة
function formatNumber(number) {
    return parseFloat(number).toLocaleString('en-US', { minimumFractionDigits: 2 });
}
