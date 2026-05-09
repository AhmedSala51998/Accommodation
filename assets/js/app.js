// app.js
document.addEventListener('DOMContentLoaded', () => {
    let allWorkers = [];
    const workerBody = document.getElementById('workerBody');
    const globalSearch = document.getElementById('globalSearch');
    const selectAll = document.getElementById('selectAll');
    const bulkEditBtn = document.getElementById('bulkEditBtn');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');

    // Modals
    const addModal = document.getElementById('addModal');
    const editModal = document.getElementById('editModal');
    const bulkAddBtn = document.getElementById('bulkAddBtn');
    const closeBtns = document.querySelectorAll('.close');

    // Fetch Data
    const fetchWorkers = async () => {
        try {
            const response = await fetch('api.php?action=fetch');
            allWorkers = await response.json();
            renderTable(allWorkers);
        } catch (error) {
            console.error('Error fetching workers:', error);
        }
    };

    // Render Table
    const renderTable = (data) => {
        workerBody.innerHTML = data.map(row => `
            <tr>
                <td><input type="checkbox" class="row-select" value="${row.id}"></td>
                <td><strong>${row.worker_name}</strong></td>
                <td>${row.passport}</td>
                <td>${row.nationality}</td>
                <td>${row.office}</td>
                <td>${row.customer}</td>
                <td>${row.national_id}</td>
                <td><span class="badge ${row.guarantee_status === 'داخل الضمان' ? 'badge-success' : 'badge-warning'}">${row.guarantee_status}</span></td>
                <td>${row.housing_location}</td>
                <td>${row.entry_date || '-'}</td>
                <td>${row.days_in_ksa || 0}</td>
                <td>${row.housing_entry_date || '-'}</td>
                <td>${row.days_in_housing || 0}</td>
                <td>${row.salary}</td>
                <td><span class="badge badge-info">${row.action_type}</span></td>
                <td>${row.ticket_info}</td>
                <td><span class="badge ${row.settlement_status === 'تم الخصم' ? 'badge-success' : 'badge-warning'}">${row.settlement_status}</span></td>
            </tr>
        `).join('');
        updateBulkButtons();
    };

    // Global Search & Filters
    const applyFilters = () => {
        const searchTerm = globalSearch.value.toLowerCase();
        const guaranteeFilter = document.getElementById('filterGuarantee').value;
        const housingFilter = document.getElementById('filterHousing').value;
        const actionFilter = document.getElementById('filterAction').value;
        const settlementFilter = document.getElementById('filterSettlement').value;

        const filtered = allWorkers.filter(row => {
            const matchesSearch = Object.values(row).some(val => String(val).toLowerCase().includes(searchTerm));
            const matchesGuarantee = !guaranteeFilter || row.guarantee_status === guaranteeFilter;
            const matchesHousing = !housingFilter || row.housing_location === housingFilter;
            const matchesAction = !actionFilter || row.action_type === actionFilter;
            const matchesSettlement = !settlementFilter || row.settlement_status === settlementFilter;

            return matchesSearch && matchesGuarantee && matchesHousing && matchesAction && matchesSettlement;
        });

        renderTable(filtered);
    };

    globalSearch.addEventListener('input', applyFilters);
    ['filterGuarantee', 'filterHousing', 'filterAction', 'filterSettlement'].forEach(id => {
        document.getElementById(id).addEventListener('change', applyFilters);
    });

    // Select All
    selectAll.addEventListener('change', () => {
        document.querySelectorAll('.row-select').forEach(cb => cb.checked = selectAll.checked);
        updateBulkButtons();
    });

    workerBody.addEventListener('change', (e) => {
        if (e.target.classList.contains('row-select')) updateBulkButtons();
    });

    function updateBulkButtons() {
        const selected = document.querySelectorAll('.row-select:checked').length;
        bulkEditBtn.disabled = selected === 0;
        bulkDeleteBtn.disabled = selected === 0;
    }

    // Toast System
    const showToast = (message, type = 'add') => {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        let icon = 'fa-check-circle';
        if (type === 'edit') icon = 'fa-sync-alt';
        if (type === 'delete') icon = 'fa-trash-alt';
        if (type === 'error') icon = 'fa-exclamation-circle';

        toast.innerHTML = `
            <i class="fas ${icon}"></i>
            <span>${message}</span>
        `;
        document.getElementById('toastContainer').appendChild(toast);
        setTimeout(() => {
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 500);
            }, 4000);
        }, 100);
    };

    // Modal Control
    bulkAddBtn.onclick = () => {
        addModal.style.display = 'block';
        if (addBody.children.length === 0) addNewRow();
    };

    bulkEditBtn.onclick = () => {
        const selectedIds = Array.from(document.querySelectorAll('.row-select:checked')).map(cb => cb.value);
        const selectedWorkers = allWorkers.filter(w => selectedIds.includes(String(w.id)));
        
        const editBody = document.getElementById('editBody');
        editBody.innerHTML = selectedWorkers.map(w => `
            <tr data-id="${w.id}">
                <td><input type="text" class="edit-name" value="${w.worker_name}" required></td>
                <td><input type="text" class="edit-passport" value="${w.passport || ''}"></td>
                <td><input type="text" class="edit-nat" value="${w.nationality || ''}"></td>
                <td><input type="text" class="edit-office" value="${w.office || ''}"></td>
                <td><input type="text" class="edit-customer" value="${w.customer || ''}"></td>
                <td><input type="text" class="edit-id" value="${w.national_id || ''}"></td>
                <td>
                    <select class="edit-guarantee">
                        <option value="داخل الضمان" ${w.guarantee_status === 'داخل الضمان' ? 'selected' : ''}>داخل الضمان</option>
                        <option value="خارج الضمان" ${w.guarantee_status === 'خارج الضمان' ? 'selected' : ''}>خارج الضمان</option>
                    </select>
                </td>
                <td>
                    <select class="edit-housing">
                        <option value="ايواء الرياض" ${w.housing_location === 'ايواء الرياض' ? 'selected' : ''}>ايواء الرياض</option>
                        <option value="ايواء ينبع" ${w.housing_location === 'ايواء ينبع' ? 'selected' : ''}>ايواء ينبع</option>
                        <option value="ايواء جدة" ${w.housing_location === 'ايواء جدة' ? 'selected' : ''}>ايواء جدة</option>
                    </select>
                </td>
                <td><input type="date" class="edit-entry" value="${w.entry_date || ''}"></td>
                <td><input type="date" class="edit-housing-date" value="${w.housing_entry_date || ''}"></td>
                <td><input type="number" class="edit-salary" value="${w.salary}" step="0.01"></td>
                <td><input type="text" class="edit-desc" value="${w.status_description || ''}"></td>
                <td>
                    <select class="edit-action">
                        <option value="السكن" ${w.action_type === 'السكن' ? 'selected' : ''}>السكن</option>
                        <option value="نقل خدمات" ${w.action_type === 'نقل خدمات' ? 'selected' : ''}>نقل خدمات</option>
                        <option value="خروج نهائي" ${w.action_type === 'خروج نهائي' ? 'selected' : ''}>خروج نهائي</option>
                        <option value="اخرى" ${w.action_type === 'اخرى' ? 'selected' : ''}>اخرى</option>
                    </select>
                </td>
                <td><input type="text" class="edit-ticket" value="${w.ticket_info || ''}"></td>
                <td>
                    <select class="edit-settlement">
                        <option value="لم يتم الخصم" ${w.settlement_status === 'لم يتم الخصم' ? 'selected' : ''}>لم يتم الخصم</option>
                        <option value="تم الخصم" ${w.settlement_status === 'تم الخصم' ? 'selected' : ''}>تم الخصم</option>
                        <option value="تم الخصم جزئياً" ${w.settlement_status === 'تم الخصم جزئياً' ? 'selected' : ''}>تم الخصم جزئياً</option>
                    </select>
                </td>
                <td><input type="text" class="edit-notes" value="${w.financial_notes || ''}"></td>
            </tr>
        `).join('');
        
        editModal.style.display = 'block';
    };

    closeBtns.forEach(btn => {
        btn.onclick = () => {
            addModal.style.display = 'none';
            editModal.style.display = 'none';
        };
    });

    window.onclick = (event) => {
        if (event.target == addModal) addModal.style.display = 'none';
        if (event.target == editModal) editModal.style.display = 'none';
    };

    // Bulk Add Logic
    const addBody = document.getElementById('addBody');
    const addNewRow = () => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" class="add-name" required placeholder="اسم العاملة"></td>
            <td><input type="text" class="add-passport" placeholder="الجواز"></td>
            <td><input type="text" class="add-nat" placeholder="الجنسية"></td>
            <td><input type="text" class="add-office" placeholder="المكتب"></td>
            <td><input type="text" class="add-customer" placeholder="العميل"></td>
            <td><input type="text" class="add-id" placeholder="الهوية"></td>
            <td>
                <select class="add-guarantee">
                    <option value="داخل الضمان">داخل الضمان</option>
                    <option value="خارج الضمان">خارج الضمان</option>
                </select>
            </td>
            <td>
                <select class="add-housing">
                    <option value="ايواء الرياض">ايواء الرياض</option>
                    <option value="ايواء ينبع">ايواء ينبع</option>
                    <option value="ايواء جدة">ايواء جدة</option>
                </select>
            </td>
            <td><input type="date" class="add-entry"></td>
            <td><input type="date" class="add-housing-date"></td>
            <td><input type="number" class="add-salary" placeholder="0.00" step="0.01"></td>
            <td><input type="text" class="add-desc" placeholder="شرح الحالة"></td>
            <td>
                <select class="add-action">
                    <option value="السكن">السكن</option>
                    <option value="نقل خدمات">نقل خدمات</option>
                    <option value="خروج نهائي">خروج نهائي</option>
                    <option value="اخرى">اخرى</option>
                </select>
            </td>
            <td><input type="text" class="add-ticket" placeholder="التذكرة"></td>
            <td>
                <select class="add-settlement">
                    <option value="لم يتم الخصم">لم يتم الخصم</option>
                    <option value="تم الخصم">تم الخصم</option>
                    <option value="تم الخصم جزئياً">تم الخصم جزئياً</option>
                </select>
            </td>
            <td><input type="text" class="add-notes" placeholder="ملاحظات مالية"></td>
            <td><button class="btn-remove" onclick="this.parentElement.parentElement.remove()"><i class="fas fa-times"></i></button></td>
        `;
        addBody.appendChild(tr);
    };

    document.getElementById('addRowBtn').onclick = addNewRow;

    document.getElementById('saveBulkAdd').onclick = async () => {
        const rows = document.querySelectorAll('#addBody tr');
        const data = Array.from(rows).map(tr => ({
            worker_name: tr.querySelector('.add-name').value,
            passport: tr.querySelector('.add-passport').value,
            nationality: tr.querySelector('.add-nat').value,
            office: tr.querySelector('.add-office').value,
            customer: tr.querySelector('.add-customer').value,
            national_id: tr.querySelector('.add-id').value,
            guarantee_status: tr.querySelector('.add-guarantee').value,
            housing_location: tr.querySelector('.add-housing').value,
            entry_date: tr.querySelector('.add-entry').value,
            housing_entry_date: tr.querySelector('.add-housing-date').value,
            salary: tr.querySelector('.add-salary').value,
            status_description: tr.querySelector('.add-desc').value,
            action_type: tr.querySelector('.add-action').value,
            ticket_info: tr.querySelector('.add-ticket').value,
            settlement_status: tr.querySelector('.add-settlement').value,
            financial_notes: tr.querySelector('.add-notes').value
        })).filter(r => r.worker_name);

        if (data.length === 0) return showToast('الرجاء تعبئة بيانات صف واحد على الأقل', 'error');

        try {
            const response = await fetch('api.php?action=bulk_add', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                addModal.style.display = 'none';
                addBody.innerHTML = '';
                showToast(`تم إضافة ${data.length} عاملة بنجاح`, 'add');
                fetchWorkers();
            } else {
                showToast(result.message, 'error');
            }
        } catch (e) {
            showToast('حدث خطأ أثناء الإضافة', 'error');
        }
    };

    // Bulk Edit Logic
    document.getElementById('saveBulkEdit').onclick = async () => {
        const rows = document.querySelectorAll('#editBody tr');
        const data = Array.from(rows).map(tr => ({
            id: tr.dataset.id,
            worker_name: tr.querySelector('.edit-name').value,
            passport: tr.querySelector('.edit-passport').value,
            nationality: tr.querySelector('.edit-nat').value,
            office: tr.querySelector('.edit-office').value,
            customer: tr.querySelector('.edit-customer').value,
            national_id: tr.querySelector('.edit-id').value,
            guarantee_status: tr.querySelector('.edit-guarantee').value,
            housing_location: tr.querySelector('.edit-housing').value,
            entry_date: tr.querySelector('.edit-entry').value,
            housing_entry_date: tr.querySelector('.edit-housing-date').value,
            salary: tr.querySelector('.edit-salary').value,
            status_description: tr.querySelector('.edit-desc').value,
            action_type: tr.querySelector('.edit-action').value,
            ticket_info: tr.querySelector('.edit-ticket').value,
            settlement_status: tr.querySelector('.edit-settlement').value,
            financial_notes: tr.querySelector('.edit-notes').value
        }));

        try {
            const response = await fetch('api.php?action=bulk_update', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                editModal.style.display = 'none';
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

    // Bulk Delete Logic
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
                showToast(`تم حذف ${selectedIds.length} عاملة بنجاح`, 'delete');
                fetchWorkers();
            } else {
                showToast(result.message, 'error');
            }
        } catch (e) {
            showToast('حدث خطأ أثناء الحذف', 'error');
        }
    };

    // PDF Export Logic (Using Browser Print for better Arabic support)
    document.getElementById('exportPdfBtn').onclick = () => {
        window.print();
    };

    fetchWorkers();
});
