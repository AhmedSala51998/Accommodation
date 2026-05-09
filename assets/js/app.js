document.addEventListener('DOMContentLoaded', () => {
    let allWorkers = [];

    // Select Elements
    const workerBody = document.getElementById('workerBody');
    const globalSearch = document.getElementById('globalSearch');
    const bulkAddBtn = document.getElementById('bulkAddBtn');
    const bulkEditBtn = document.getElementById('bulkEditBtn');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const addModal = document.getElementById('addModal');
    const editModal = document.getElementById('editModal');
    const detailModal = document.getElementById('detailModal');
    const closeBtns = document.querySelectorAll('.close');

    // Shared Dropdown Options
    const nationalityOptions = `
        <option value="اثيوبيا">اثيوبيا</option>
        <option value="بوروندي">بوروندي</option>
        <option value="الفلبين">الفلبين</option>
        <option value="سريلانكا">سريلانكا</option>
        <option value="اوغندا">اوغندا</option>
        <option value="كينيا">كينيا</option>
        <option value="الهند">الهند</option>
        <option value="بنجلاديش">بنجلاديش</option>
    `;

    const housingOptions = `
        <option value="الرياض">الرياض</option>
        <option value="جدة">جدة</option>
        <option value="ينبع">ينبع</option>
    `;

    const actionOptions = `
        <option value="السكن">السكن</option>
        <option value="نقل خدمات">نقل خدمات</option>
        <option value="خروج نهائي">خروج نهائي</option>
        <option value="هروب">هروب</option>
        <option value="اخرى">اخرى</option>
    `;

    const settlementOptions = `
        <option value="لم يتم الخصم">لم يتم الخصم</option>
        <option value="تم الخصم">تم الخصم</option>
        <option value="تم الخصم جزئياً">تم الخصم جزئياً</option>
        <option value="لا تخصم">لا تخصم</option>
    `;

    // Detail Popups
    window.showDetail = (title, content) => {
        document.getElementById('detailTitle').innerText = title;
        document.getElementById('detailContent').innerText = content || 'لا يوجد بيانات';
        detailModal.style.display = 'block';
    };

    // Fetch Workers
    const fetchWorkers = async () => {
        try {
            const response = await fetch('api.php?action=list');
            allWorkers = await response.json();
            applyFilters();
        } catch (e) {
            showToast('حدث خطأ أثناء جلب البيانات', 'error');
        }
    };

    // Render Table
    const renderTable = (data) => {
        workerBody.innerHTML = data.map((row, index) => `
            <tr>
                <td><input type="checkbox" class="row-select" value="${row.id}"></td>
                <td>${index + 1}</td>
                <td><strong>${row.worker_name}</strong></td>
                <td>${row.passport || ''}</td>
                <td>${row.nationality || ''}</td>
                <td>${row.office || ''}</td>
                <td>${row.customer || ''}</td>
                <td>${row.national_id || ''}</td>
                <td><span class="badge ${row.guarantee_status === 'داخل الضمان' ? 'badge-success' : 'badge-danger'}">${row.guarantee_status}</span></td>
                <td>${row.housing_location || ''}</td>
                <td>${row.entry_date || ''}</td>
                <td>${row.days_in_ksa || 0}</td>
                <td>${row.housing_entry_date || ''}</td>
                <td>${row.days_in_housing || 0}</td>
                <td>${parseFloat(row.salary).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                <td>${row.action_type || ''}</td>
                <td>${row.ticket_info || ''}</td>
                <td>${row.settlement_status || ''}</td>
                <td class="details-cell">
                    <button class="icon-btn btn-info" onclick="showDetail('شرح الحالة', \`${row.status_description || ''}\`)" title="شرح الحالة">
                        <i class="fas fa-comment-alt"></i>
                    </button>
                    <button class="icon-btn btn-note" onclick="showDetail('الملاحظات المالية', \`${row.financial_notes || ''}\`)" title="الملاحظات المالية">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </button>
                </td>
            </tr>
        `).join('');

        document.querySelectorAll('.row-select').forEach(cb => {
            cb.addEventListener('change', updateSelectionState);
        });
        updateSelectionState();
    };

    const updateSelectionState = () => {
        const selectedCount = document.querySelectorAll('.row-select:checked').length;
        bulkEditBtn.disabled = selectedCount === 0;
        bulkDeleteBtn.disabled = selectedCount === 0;
        document.getElementById('selectAll').checked = selectedCount > 0 && selectedCount === document.querySelectorAll('.row-select').length;
    };

    document.getElementById('selectAll').addEventListener('change', (e) => {
        document.querySelectorAll('.row-select').forEach(cb => {
            cb.checked = e.target.checked;
        });
        updateSelectionState();
    });

    // Filtering
    const applyFilters = () => {
        const searchTerm = globalSearch.value.toLowerCase();
        const guaranteeFilter = document.getElementById('filterGuarantee').value;
        const nationalityFilter = document.getElementById('filterNationality').value;
        const housingFilter = document.getElementById('filterHousing').value;
        const actionFilter = document.getElementById('filterAction').value;
        const settlementFilter = document.getElementById('filterSettlement').value;

        const filtered = allWorkers.filter(row => {
            const matchesSearch = Object.values(row).some(val => String(val).toLowerCase().includes(searchTerm));
            const matchesGuarantee = !guaranteeFilter || row.guarantee_status === guaranteeFilter;
            const matchesNat = !nationalityFilter || row.nationality === nationalityFilter;
            const matchesHousing = !housingFilter || row.housing_location === housingFilter;
            const matchesAction = !actionFilter || row.action_type === actionFilter;
            const matchesSettlement = !settlementFilter || row.settlement_status === settlementFilter;

            return matchesSearch && matchesGuarantee && matchesNat && matchesHousing && matchesAction && matchesSettlement;
        });

        renderTable(filtered);
    };

    globalSearch.addEventListener('input', applyFilters);
    ['filterGuarantee', 'filterNationality', 'filterHousing', 'filterAction', 'filterSettlement'].forEach(id => {
        document.getElementById(id).addEventListener('change', applyFilters);
    });

    // Modal Control
    bulkAddBtn.onclick = () => {
        addModal.style.display = 'block';
        if (addBody.children.length === 0) addNewRow();
    };

    bulkEditBtn.onclick = () => {
        const selectedIds = Array.from(document.querySelectorAll('.row-select:checked')).map(cb => cb.value);
        const selectedWorkers = allWorkers.filter(w => selectedIds.includes(String(w.id)));
        
        const editBody = document.getElementById('editBody');
        editBody.innerHTML = selectedWorkers.map((w, index) => `
            <div class="worker-row-group" data-id="${w.id}">
                <div class="row-group-header">#${index + 1} - ${w.worker_name}</div>
                <div class="form-row">
                    <div class="field"><label>اسم العاملة</label><input type="text" class="edit-name" value="${w.worker_name}" required></div>
                    <div class="field"><label>الجواز</label><input type="text" class="edit-passport" value="${w.passport || ''}"></div>
                    <div class="field"><label>الجنسية</label><select class="edit-nat">${nationalityOptions.replace(`value="${w.nationality}"`, `value="${w.nationality}" selected`)}</select></div>
                    <div class="field"><label>المكتب</label><input type="text" class="edit-office" value="${w.office || ''}"></div>
                    <div class="field"><label>العميل</label><input type="text" class="edit-customer" value="${w.customer || ''}"></div>
                    <div class="field"><label>الهوية</label><input type="text" class="edit-id" value="${w.national_id || ''}"></div>
                    <div class="field"><label>حالة الضمان</label><select class="edit-guarantee"><option value="داخل الضمان" ${w.guarantee_status === 'داخل الضمان' ? 'selected' : ''}>داخل الضمان</option><option value="خارج الضمان" ${w.guarantee_status === 'خارج الضمان' ? 'selected' : ''}>خارج الضمان</option></select></div>
                    <div class="field"><label>الموقع</label><select class="edit-housing">${housingOptions.replace(`value="${w.housing_location}"`, `value="${w.housing_location}" selected`)}</select></div>
                </div>
                <div class="form-row">
                    <div class="field"><label>دخول المملكة</label><input type="date" class="edit-entry" value="${w.entry_date || ''}"></div>
                    <div class="field"><label>دخول الإيواء</label><input type="date" class="edit-housing-date" value="${w.housing_entry_date || ''}"></div>
                    <div class="field"><label>الراتب</label><input type="number" class="edit-salary" value="${w.salary}" step="0.01"></div>
                    <div class="field"><label>شرح الحالة</label><input type="text" class="edit-desc" value="${w.status_description || ''}"></div>
                    <div class="field"><label>الإجراء</label><select class="edit-action">${actionOptions.replace(`value="${w.action_type}"`, `value="${w.action_type}" selected`)}</select></div>
                    <div class="field"><label>التذكرة</label><input type="text" class="edit-ticket" value="${w.ticket_info || ''}"></div>
                    <div class="field"><label>التسوية</label><select class="edit-settlement">${settlementOptions.replace(`value="${w.settlement_status}"`, `value="${w.settlement_status}" selected`)}</select></div>
                    <div class="field"><label>ملاحظات</label><input type="text" class="edit-notes" value="${w.financial_notes || ''}"></div>
                </div>
            </div>
        `).join('');
        editModal.style.display = 'block';
    };

    closeBtns.forEach(btn => {
        btn.onclick = () => {
            addModal.style.display = 'none';
            editModal.style.display = 'none';
            detailModal.style.display = 'none';
        };
    });

    window.onclick = (event) => {
        if (event.target == addModal) addModal.style.display = 'none';
        if (event.target == editModal) editModal.style.display = 'none';
        if (event.target == detailModal) detailModal.style.display = 'none';
    };

    const addNewRow = () => {
        const group = document.createElement('div');
        group.className = 'worker-row-group';
        const index = addBody.children.length + 1;
        group.innerHTML = `
            <div class="row-group-header">إضافة عاملة جديدة #${index} <button class="btn-remove-group" onclick="this.parentElement.parentElement.remove()"><i class="fas fa-trash"></i></button></div>
            <div class="form-row">
                <div class="field"><label>اسم العاملة</label><input type="text" class="add-name" required placeholder="اسم العاملة"></div>
                <div class="field"><label>الجواز</label><input type="text" class="add-passport" placeholder="الجواز"></div>
                <div class="field"><label>الجنسية</label><select class="add-nat">${nationalityOptions}</select></div>
                <div class="field"><label>المكتب</label><input type="text" class="add-office" placeholder="المكتب"></div>
                <div class="field"><label>العميل</label><input type="text" class="add-customer" placeholder="العميل"></div>
                <div class="field"><label>الهوية</label><input type="text" class="add-id" placeholder="الهوية"></div>
                <div class="field"><label>حالة الضمان</label><select class="add-guarantee"><option value="داخل الضمان">داخل الضمان</option><option value="خارج الضمان">خارج الضمان</option></select></div>
                <div class="field"><label>الموقع</label><select class="add-housing">${housingOptions}</select></div>
            </div>
            <div class="form-row">
                <div class="field"><label>دخول المملكة</label><input type="date" class="add-entry"></div>
                <div class="field"><label>دخول الإيواء</label><input type="date" class="add-housing-date"></div>
                <div class="field"><label>الراتب</label><input type="number" class="add-salary" placeholder="0.00" step="0.01"></div>
                <div class="field"><label>شرح الحالة</label><input type="text" class="add-desc" placeholder="شرح الحالة"></div>
                <div class="field"><label>الإجراء</label><select class="add-action">${actionOptions}</select></div>
                <div class="field"><label>التذكرة</label><input type="text" class="add-ticket" placeholder="التذكرة"></div>
                <div class="field"><label>التسوية</label><select class="add-settlement">${settlementOptions}</select></div>
                <div class="field"><label>ملاحظات</label><input type="text" class="add-notes" placeholder="ملاحظات مالية"></div>
            </div>
        `;
        addBody.appendChild(group);
    };

    document.getElementById('addRowBtn').onclick = addNewRow;

    const clearFilters = () => {
        globalSearch.value = '';
        ['filterGuarantee', 'filterNationality', 'filterHousing', 'filterAction', 'filterSettlement'].forEach(id => {
            document.getElementById(id).value = '';
        });
    };

    document.getElementById('saveBulkAdd').onclick = async () => {
        const groups = document.querySelectorAll('#addBody .worker-row-group');
        const data = Array.from(groups).map(group => ({
            worker_name: group.querySelector('.add-name').value,
            passport: group.querySelector('.add-passport').value,
            nationality: group.querySelector('.add-nat').value,
            office: group.querySelector('.add-office').value,
            customer: group.querySelector('.add-customer').value,
            national_id: group.querySelector('.add-id').value,
            guarantee_status: group.querySelector('.add-guarantee').value,
            housing_location: group.querySelector('.add-housing').value,
            entry_date: group.querySelector('.add-entry').value,
            housing_entry_date: group.querySelector('.add-housing-date').value,
            salary: group.querySelector('.add-salary').value,
            status_description: group.querySelector('.add-desc').value,
            action_type: group.querySelector('.add-action').value,
            ticket_info: group.querySelector('.add-ticket').value,
            settlement_status: group.querySelector('.add-settlement').value,
            financial_notes: group.querySelector('.add-notes').value
        })).filter(r => r.worker_name);

        if (data.length === 0) return showToast('الرجاء تعبئة بيانات عاملة واحدة على الأقل', 'error');

        try {
            const response = await fetch('api.php?action=bulk_add', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                addModal.style.display = 'none';
                addBody.innerHTML = '';
                clearFilters();
                showToast(`تم إضافة ${data.length} عاملة بنجاح`, 'add');
                fetchWorkers();
            } else {
                showToast(result.message, 'error');
            }
        } catch (e) {
            showToast('حدث خطأ أثناء الإضافة', 'error');
        }
    };

    document.getElementById('saveBulkEdit').onclick = async () => {
        const groups = document.querySelectorAll('#editBody .worker-row-group');
        const data = Array.from(groups).map(group => ({
            id: group.dataset.id,
            worker_name: group.querySelector('.edit-name').value,
            passport: group.querySelector('.edit-passport').value,
            nationality: group.querySelector('.edit-nat').value,
            office: group.querySelector('.edit-office').value,
            customer: group.querySelector('.edit-customer').value,
            national_id: group.querySelector('.edit-id').value,
            guarantee_status: group.querySelector('.edit-guarantee').value,
            housing_location: group.querySelector('.edit-housing').value,
            entry_date: group.querySelector('.edit-entry').value,
            housing_entry_date: group.querySelector('.edit-housing-date').value,
            salary: group.querySelector('.edit-salary').value,
            status_description: group.querySelector('.edit-desc').value,
            action_type: group.querySelector('.edit-action').value,
            ticket_info: group.querySelector('.edit-ticket').value,
            settlement_status: group.querySelector('.edit-settlement').value,
            financial_notes: group.querySelector('.edit-notes').value
        }));

        try {
            const response = await fetch('api.php?action=bulk_update', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                editModal.style.display = 'none';
                clearFilters();
                if (result.affected_count > 0) {
                    showToast(`تم تحديث ${result.affected_count} سجل بنجاح من أصل ${data.length}`, 'edit');
                } else {
                    showToast(`لم يتم تغيير أي بيانات في السجلات الـ ${data.length} المختارة`, 'edit');
                }
                fetchWorkers();
            } else {
                showToast(result.message, 'error');
            }
        } catch (e) {
            showToast('حدث خطأ أثناء التعديل', 'error');
        }
    };

    document.getElementById('bulkDeleteBtn').onclick = async () => {
        if (!confirm('هل أنت متأكد من حذف البيانات المحددة؟')) return;
        const selectedIds = Array.from(document.querySelectorAll('.row-select:checked')).map(cb => cb.value);

        try {
            const response = await fetch('api.php?action=bulk_delete', {
                method: 'POST',
                body: JSON.stringify({ ids: selectedIds })
            });
            const result = await response.json();
            if (result.success) {
                clearFilters();
                showToast(`تم حذف ${selectedIds.length} عاملة بنجاح`, 'delete');
                fetchWorkers();
            } else {
                showToast(result.message, 'error');
            }
        } catch (e) {
            showToast('حدث خطأ أثناء الحذف', 'error');
        }
    };

    document.getElementById('exportPdfBtn').onclick = () => {
        window.print();
    };

    fetchWorkers();
});

const showToast = (message, type) => {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let icon = 'info-circle';
    if (type === 'add') icon = 'check-circle';
    if (type === 'edit') icon = 'sync-alt';
    if (type === 'delete') icon = 'trash-alt';
    if (type === 'error') icon = 'exclamation-triangle';

    toast.innerHTML = `
        <i class="fas fa-${icon}"></i>
        <span>${message}</span>
    `;
    
    container.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 500);
    }, 4000);
};
