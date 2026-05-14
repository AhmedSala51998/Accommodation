document.addEventListener('DOMContentLoaded', () => {
    let allWorkers = [];
    let allOffices = [];
    const isArchivePage = typeof PAGE_TYPE !== 'undefined' && PAGE_TYPE === 'archive';

    // Select Elements
    const workerBody = document.getElementById('workerBody');
    const globalSearch = document.getElementById('globalSearch');
    const bulkAddBtn = document.getElementById('bulkAddBtn');
    const bulkEditBtn = document.getElementById('bulkEditBtn');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const bulkArchiveBtn = document.getElementById('bulkArchiveBtn');
    const bulkUnarchiveBtn = document.getElementById('bulkUnarchiveBtn');

    // Office Elements
    const manageOfficesBtn = document.getElementById('manageOfficesBtn');
    const officesModal = document.getElementById('officesModal');
    const officesBody = document.getElementById('officesBody');
    const showAddOfficesBtn = document.getElementById('showAddOfficesBtn');
    const bulkEditOfficesBtn = document.getElementById('bulkEditOfficesBtn');
    const bulkDeleteOfficesBtn = document.getElementById('bulkDeleteOfficesBtn');
    const addOfficesModal = document.getElementById('addOfficesModal');
    const officeInputsContainer = document.getElementById('officeInputsContainer');
    const saveOfficesBtn = document.getElementById('saveOfficesBtn');
    const addOfficeInputBtn = document.getElementById('addOfficeInputBtn');
    const filterOffice = document.getElementById('filterOffice');

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

    // Global function for showing details
    window.showDetail = (title, content) => {
        document.getElementById('detailTitle').innerText = title;
        document.getElementById('detailContent').innerText = content || 'لا يوجد بيانات';
        detailModal.style.display = 'block';
    };

    // Fetch Workers
    const fetchWorkers = async () => {
        try {
            const archivedParam = isArchivePage ? '1' : '0';
            const response = await fetch(`api.php?action=list&archived=${archivedParam}`);
            allWorkers = await response.json();
            applyFilters();
        } catch (e) {
            showToast('حدث خطأ أثناء جلب البيانات', 'error');
        }
    };

    // Fetch Offices
    const fetchOffices = async () => {
        try {
            const response = await fetch('api.php?action=list_offices');
            allOffices = await response.json();
            populateOfficeFilters();
            if (officesBody) renderOfficesCards();
        } catch (e) {
            console.error('Error fetching offices', e);
        }
    };

    const populateOfficeFilters = () => {
        if (!filterOffice) return;
        const options = allOffices.map(o => `<option value="${o.name}">${o.name}</option>`).join('');
        filterOffice.innerHTML = `<option value="">كل المكاتب</option>${options}`;
    };

    const getOfficeOptions = (selectedName = '') => {
        return allOffices.map(o => `<option value="${o.name}" ${o.name === selectedName ? 'selected' : ''}>${o.name}</option>`).join('');
    };

    const renderOfficesCards = () => {
        officesBody.innerHTML = allOffices.map((office, index) => `
            <div class="office-card" onclick="toggleOfficeCard(this)">
                <div style="position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.05); padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; color: #64748b;">#${index + 1}</div>
                <input type="checkbox" class="office-select" value="${office.id}" onclick="event.stopPropagation(); updateOfficeSelectionState();">
                <div class="office-icon"><i class="fas fa-building"></i></div>
                <div class="office-name">${office.name}</div>
            </div>
        `).join('');

        updateOfficeSelectionState();
    };

    window.toggleOfficeCard = (card) => {
        const cb = card.querySelector('.office-select');
        cb.checked = !cb.checked;
        updateOfficeSelectionState();
    };

    const updateOfficeSelectionState = () => {
        const selectedCount = document.querySelectorAll('.office-select:checked').length;
        if (bulkEditOfficesBtn) bulkEditOfficesBtn.disabled = selectedCount === 0;
        if (bulkDeleteOfficesBtn) bulkDeleteOfficesBtn.disabled = selectedCount === 0;
        
        const selectAllOffices = document.getElementById('selectAllOffices');
        if (selectAllOffices) {
            selectAllOffices.checked = selectedCount > 0 && selectedCount === document.querySelectorAll('.office-select').length;
        }

        // Visual feedback for cards
        document.querySelectorAll('.office-card').forEach(card => {
            if (card.querySelector('.office-select').checked) {
                card.style.borderColor = 'var(--primary-color)';
                card.style.background = '#eef2ff';
            } else {
                card.style.borderColor = 'var(--border-color)';
                card.style.background = 'var(--office-card-gradient)';
            }
        });
        updateOfficeRangeInputs();
    };

    const updateOfficeRangeInputs = () => {
        const checked = Array.from(document.querySelectorAll('.office-select')).map((cb, i) => cb.checked ? i + 1 : null).filter(n => n !== null);
        const rangeFrom = document.getElementById('officeRangeFrom');
        const rangeTo = document.getElementById('officeRangeTo');
        
        if (!rangeFrom || !rangeTo) return;

        if (checked.length > 0) {
            rangeFrom.value = Math.min(...checked);
            rangeTo.value = Math.max(...checked);
        } else {
            rangeFrom.value = '';
            rangeTo.value = '';
        }
    };

    // Office Range Selection Logic (Automatic)
    const handleOfficeRangeSelect = () => {
        let from = parseInt(document.getElementById('officeRangeFrom').value);
        let to = parseInt(document.getElementById('officeRangeTo').value);

        if (isNaN(from) || isNaN(to) || from < 1 || from > to) return;

        const checkboxes = document.querySelectorAll('.office-select');
        checkboxes.forEach((cb, index) => {
            const num = index + 1;
            cb.checked = (num >= from && num <= to);
        });
        updateOfficeSelectionState();
    };

    if (document.getElementById('officeRangeFrom')) {
        document.getElementById('officeRangeFrom').addEventListener('input', handleOfficeRangeSelect);
        document.getElementById('officeRangeTo').addEventListener('input', handleOfficeRangeSelect);
    }

    const updateRangeInputs = () => {
        const checked = Array.from(document.querySelectorAll('.row-select')).map((cb, i) => cb.checked ? i + 1 : null).filter(n => n !== null);
        const rangeFrom = document.getElementById('rangeFrom');
        const rangeTo = document.getElementById('rangeTo');
        
        if (!rangeFrom || !rangeTo) return;

        if (checked.length > 0) {
            const min = Math.min(...checked);
            const max = Math.max(...checked);
            rangeFrom.value = min;
            rangeTo.value = max;
        } else {
            rangeFrom.value = '';
            rangeTo.value = '';
        }
    };

    // Office Card Search Logic (Global Listener)
    $(document).on('input', '#officeSearchInput', function() {
        const term = $(this).val().toLowerCase();
        $('.office-card').each(function() {
            const name = $(this).find('.office-name').text().toLowerCase();
            if (name.includes(term)) {
                $(this).show().css('display', 'flex');
            } else {
                $(this).hide();
            }
        });
    });

    if (document.getElementById('selectAllOffices')) {
        document.getElementById('selectAllOffices').addEventListener('change', (e) => {
            document.querySelectorAll('.office-select').forEach(cb => {
                cb.checked = e.target.checked;
            });
            updateOfficeSelectionState();
        });
    }

    // Render Main Table
    let lastChecked = null;
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
                <td class="details-cell no-print">
                    <button class="icon-btn btn-info" onclick="showDetail('شرح الحالة', \`${row.status_description || ''}\`)" title="شرح الحالة">
                        <i class="fas fa-comment-alt"></i>
                    </button>
                    <button class="icon-btn btn-note" onclick="showDetail('الملاحظات المالية', \`${row.financial_notes || ''}\`)" title="الملاحظات المالية">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </button>
                </td>
            </tr>
        `).join('');

        const checkboxes = document.querySelectorAll('.row-select');
        checkboxes.forEach((cb, index) => {
            cb.onclick = (e) => {
                if (e.shiftKey && lastChecked !== null) {
                    let start = Math.min(index, lastChecked);
                    let end = Math.max(index, lastChecked);
                    for (let i = start; i <= end; i++) {
                        checkboxes[i].checked = checkboxes[lastChecked].checked;
                    }
                }
                lastChecked = index;
                updateSelectionState();
                updateRangeInputs();
            };
        });
        updateSelectionState();
    };

    const updateSelectionState = () => {
        const selectedCount = document.querySelectorAll('.row-select:checked').length;
        if (bulkEditBtn) bulkEditBtn.disabled = selectedCount === 0;
        if (bulkDeleteBtn) bulkDeleteBtn.disabled = selectedCount === 0;
        if (bulkArchiveBtn) bulkArchiveBtn.disabled = selectedCount === 0;
        if (bulkUnarchiveBtn) bulkUnarchiveBtn.disabled = selectedCount === 0;
        document.getElementById('selectAll').checked = selectedCount > 0 && selectedCount === document.querySelectorAll('.row-select').length;
        updateRangeInputs();
    };

    document.getElementById('selectAll').addEventListener('change', (e) => {
        document.querySelectorAll('.row-select').forEach(cb => {
            cb.checked = e.target.checked;
        });
        updateSelectionState();
    });

    // Filtering Logic
    const applyFilters = () => {
        const searchTerm = globalSearch.value.toLowerCase();
        const guaranteeFilter = document.getElementById('filterGuarantee').value;
        const nationalityFilter = document.getElementById('filterNationality').value;
        const housingFilter = document.getElementById('filterHousing').value;
        const officeFilterVal = filterOffice ? filterOffice.value : '';
        const actionFilter = document.getElementById('filterAction').value;
        const settlementFilter = document.getElementById('filterSettlement').value;

        const filtered = allWorkers.filter(row => {
            const matchesSearch = Object.values(row).some(val => String(val).toLowerCase().includes(searchTerm));
            const matchesGuarantee = !guaranteeFilter || row.guarantee_status === guaranteeFilter;
            const matchesNat = !nationalityFilter || row.nationality === nationalityFilter;
            const matchesHousing = !housingFilter || row.housing_location === housingFilter;
            const matchesOffice = !officeFilterVal || row.office === officeFilterVal;
            const matchesAction = !actionFilter || row.action_type === actionFilter;
            const matchesSettlement = !settlementFilter || row.settlement_status === settlementFilter;

            return matchesSearch && matchesGuarantee && matchesNat && matchesHousing && matchesOffice && matchesAction && matchesSettlement;
        });

        renderTable(filtered);
    };

    globalSearch.addEventListener('input', applyFilters);
    ['filterGuarantee', 'filterNationality', 'filterHousing', 'filterOffice', 'filterAction', 'filterSettlement'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', applyFilters);
    });

    // Office Modal Controls
    if (manageOfficesBtn) {
        manageOfficesBtn.onclick = () => {
            officesModal.style.display = 'block';
            fetchOffices();
        };
    }

    if (showAddOfficesBtn) {
        showAddOfficesBtn.onclick = () => {
            document.getElementById('officeModalTitle').innerText = 'إضافة مكاتب جديدة';
            officeInputsContainer.innerHTML = '';
            addOfficeInput();
            addOfficesModal.style.display = 'block';
        };
    }

    const addOfficeInput = (val = '', id = '') => {
        const div = document.createElement('div');
        div.className = 'field-with-action';
        div.style.marginBottom = '10px';
        div.innerHTML = `
            <div class="field" style="flex: 1;">
                <input type="text" class="office-name-input" value="${val}" placeholder="اسم المكتب" data-id="${id}" required>
            </div>
            <button class="icon-btn btn-danger-sm" onclick="this.parentElement.remove()" title="حذف الحقل">
                <i class="fas fa-times"></i>
            </button>
        `;
        officeInputsContainer.appendChild(div);
    };

    if (addOfficeInputBtn) {
        addOfficeInputBtn.onclick = () => addOfficeInput();
    }

    if (bulkEditOfficesBtn) {
        bulkEditOfficesBtn.onclick = () => {
            const selectedIds = Array.from(document.querySelectorAll('.office-select:checked')).map(cb => cb.value);
            const selectedOffices = allOffices.filter(o => selectedIds.includes(String(o.id)));

            document.getElementById('officeModalTitle').innerText = 'تعديل المكاتب المختارة';
            officeInputsContainer.innerHTML = '';
            selectedOffices.forEach(o => addOfficeInput(o.name, o.id));
            addOfficesModal.style.display = 'block';
        };
    }

    if (saveOfficesBtn) {
        saveOfficesBtn.onclick = async () => {
            const inputs = document.querySelectorAll('.office-name-input');
            const data = Array.from(inputs).map(input => ({
                id: input.dataset.id,
                name: input.value
            })).filter(o => o.name.trim() !== '');

            if (data.length === 0) return showToast('الرجاء إدخال اسم مكتب واحد على الأقل', 'error');

            const isEdit = data.some(o => o.id);
            const action = isEdit ? 'bulk_update_offices' : 'bulk_add_offices';

            try {
                const response = await fetch(`api.php?action=${action}`, {
                    method: 'POST',
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    addOfficesModal.style.display = 'none';
                    showToast('تم حفظ المكاتب بنجاح', 'add');
                    fetchOffices();
                } else {
                    showToast(result.message, 'error');
                }
            } catch (e) {
                showToast('حدث خطأ أثناء حفظ المكاتب', 'error');
            }
        };
    }

    if (bulkDeleteOfficesBtn) {
        bulkDeleteOfficesBtn.onclick = async () => {
            if (!confirm('هل أنت متأكد من حذف المكاتب المختارة؟')) return;
            const selectedIds = Array.from(document.querySelectorAll('.office-select:checked')).map(cb => cb.value);

            try {
                const response = await fetch('api.php?action=bulk_delete_offices', {
                    method: 'POST',
                    body: JSON.stringify({ ids: selectedIds })
                });
                const result = await response.json();
                if (result.success) {
                    showToast('تم حذف المكاتب بنجاح', 'delete');
                    fetchOffices();
                } else {
                    showToast(result.message, 'error');
                }
            } catch (e) {
                showToast('حدث خطأ أثناء الحذف', 'error');
            }
        };
    }

    // Worker Modal Controls
    if (bulkAddBtn) {
        bulkAddBtn.onclick = () => {
            addModal.style.display = 'block';
            if (addBody.children.length === 0) addNewRow();
        };
    }

    if (bulkEditBtn) {
        bulkEditBtn.onclick = () => {
            const selectedIds = Array.from(document.querySelectorAll('.row-select:checked')).map(cb => cb.value);
            const selectedWorkers = allWorkers.filter(w => selectedIds.includes(String(w.id)));

            const editBody = document.getElementById('editBody');
            editBody.innerHTML = selectedWorkers.map((w, index) => `
                <div class="worker-row-group" data-id="${w.id}">
                    <div class="row-group-header">#${index + 1} - ${w.worker_name}</div>
                    <div class="form-row">
                        <div class="field"><label>اسم العاملة</label><input type="text" class="edit-name" value="${w.worker_name}" required></div>
                        <div class="field"><label>الجواز</label><input type="text" class="edit-passport" value="${w.passport || ''}" required></div>
                        <div class="field"><label>الجنسية</label><select class="edit-nat" required>${nationalityOptions.replace(`value="${w.nationality}"`, `value="${w.nationality}" selected`)}</select></div>
                        <div class="field"><label>المكتب</label><select class="edit-office" required><option value="">اختر المكتب</option>${getOfficeOptions(w.office)}</select></div>
                        <div class="field"><label>العميل</label><input type="text" class="edit-customer" value="${w.customer || ''}" required></div>
                        <div class="field"><label>الهوية</label><input type="text" class="edit-id" value="${w.national_id || ''}" required></div>
                        <div class="field"><label>حالة الضمان</label><select class="edit-guarantee" required><option value="داخل الضمان" ${w.guarantee_status === 'داخل الضمان' ? 'selected' : ''}>داخل الضمان</option><option value="خارج الضمان" ${w.guarantee_status === 'خارج الضمان' ? 'selected' : ''}>خارج الضمان</option></select></div>
                        <div class="field"><label>الموقع</label><select class="edit-housing" required>${housingOptions.replace(`value="${w.housing_location}"`, `value="${w.housing_location}" selected`)}</select></div>
                    </div>
                    <div class="form-row">
                        <div class="field"><label>دخول المملكة</label><input type="date" class="edit-entry" value="${w.entry_date || ''}" required></div>
                        <div class="field"><label>دخول الإيواء</label><input type="date" class="edit-housing-date" value="${w.housing_entry_date || ''}" required></div>
                        <div class="field"><label>الراتب</label><input type="number" class="edit-salary" value="${w.salary}" step="0.01" required></div>
                        <div class="field"><label>شرح الحالة</label><input type="text" class="edit-desc" value="${w.status_description || ''}" required></div>
                        <div class="field"><label>الإجراء</label><select class="edit-action" required>${actionOptions.replace(`value="${w.action_type}"`, `value="${w.action_type}" selected`)}</select></div>
                        <div class="field"><label>التذكرة</label><input type="text" class="edit-ticket" value="${w.ticket_info || ''}" required></div>
                        <div class="field"><label>التسوية</label><select class="edit-settlement" required>${settlementOptions.replace(`value="${w.settlement_status}"`, `value="${w.settlement_status}" selected`)}</select></div>
                        <div class="field"><label>ملاحظات</label><input type="text" class="edit-notes" value="${w.financial_notes || ''}"></div>
                    </div>
                </div>
            `).join('');
            editModal.style.display = 'block';
        };
    }

    closeBtns.forEach(btn => {
        btn.onclick = () => {
            if (addModal) addModal.style.display = 'none';
            if (editModal) editModal.style.display = 'none';
            if (detailModal) detailModal.style.display = 'none';
            if (officesModal) officesModal.style.display = 'none';
            if (addOfficesModal) addOfficesModal.style.display = 'none';
        };
    });

    window.onclick = (event) => {
        if (event.target == addModal) addModal.style.display = 'none';
        if (event.target == editModal) editModal.style.display = 'none';
        if (event.target == detailModal) detailModal.style.display = 'none';
        if (event.target == officesModal) officesModal.style.display = 'none';
        if (event.target == addOfficesModal) addOfficesModal.style.display = 'none';
    };

    // Bulk Add Row Logic
    const addBody = document.getElementById('addBody');
    const addNewRow = () => {
        if (!addBody) return;
        const group = document.createElement('div');
        group.className = 'worker-row-group';
        const index = addBody.children.length + 1;
        group.innerHTML = `
            <div class="row-group-header">إضافة عاملة جديدة #${index} <button class="btn-remove-group" onclick="this.parentElement.parentElement.remove()"><i class="fas fa-trash"></i></button></div>
            <div class="form-row">
                <div class="field"><label>اسم العاملة</label><input type="text" class="add-name" required placeholder="اسم العاملة"></div>
                <div class="field"><label>الجواز</label><input type="text" class="add-passport" required placeholder="الجواز"></div>
                <div class="field"><label>الجنسية</label><select class="add-nat" required>${nationalityOptions}</select></div>
                <div class="field"><label>المكتب</label><select class="add-office" required><option value="">اختر المكتب</option>${getOfficeOptions()}</select></div>
                <div class="field"><label>العميل</label><input type="text" class="add-customer" required placeholder="العميل"></div>
                <div class="field"><label>الهوية</label><input type="text" class="add-id" required placeholder="الهوية"></div>
                <div class="field"><label>حالة الضمان</label><select class="add-guarantee" required><option value="داخل الضمان">داخل الضمان</option><option value="خارج الضمان">خارج الضمان</option></select></div>
                <div class="field"><label>الموقع</label><select class="add-housing" required>${housingOptions}</select></div>
            </div>
            <div class="form-row">
                <div class="field"><label>دخول المملكة</label><input type="date" class="add-entry" required></div>
                <div class="field"><label>دخول الإيواء</label><input type="date" class="add-housing-date" required></div>
                <div class="field"><label>الراتب</label><input type="number" class="add-salary" required placeholder="0.00" step="0.01"></div>
                <div class="field"><label>شرح الحالة</label><input type="text" class="add-desc" required placeholder="شرح الحالة"></div>
                <div class="field"><label>الإجراء</label><select class="add-action" required>${actionOptions}</select></div>
                <div class="field"><label>التذكرة</label><input type="text" class="add-ticket" required placeholder="التذكرة"></div>
                <div class="field"><label>التسوية</label><select class="add-settlement" required>${settlementOptions}</select></div>
                <div class="field"><label>ملاحظات</label><input type="text" class="add-notes" placeholder="ملاحظات مالية"></div>
            </div>
        `;
        addBody.appendChild(group);
    };

    if (document.getElementById('addRowBtn')) {
        document.getElementById('addRowBtn').onclick = addNewRow;
    }

    const clearFilters = () => {
        globalSearch.value = '';
        ['filterGuarantee', 'filterNationality', 'filterHousing', 'filterOffice', 'filterAction', 'filterSettlement'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
    };

    // Save Bulk Add
    if (document.getElementById('saveBulkAdd')) {
        document.getElementById('saveBulkAdd').onclick = async () => {
            const groups = document.querySelectorAll('#addBody .worker-row-group');

            // Manual validation check before sending
            let allValid = true;
            const data = Array.from(groups).map(group => {
                const rowData = {
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
                };

                // Check mandatory fields
                const mandatoryFields = ['worker_name', 'passport', 'nationality', 'office', 'customer', 'national_id', 'guarantee_status', 'housing_location', 'entry_date', 'housing_entry_date', 'salary', 'status_description', 'action_type', 'ticket_info', 'settlement_status'];
                mandatoryFields.forEach(f => {
                    if (!rowData[f] || rowData[f] === '') allValid = false;
                });

                return rowData;
            }).filter(r => r.worker_name);

            if (!allValid) return showToast('يرجى تعبئة جميع الحقول الإلزامية لكل عاملة', 'error');
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
    }

    // Save Bulk Edit
    if (document.getElementById('saveBulkEdit')) {
        document.getElementById('saveBulkEdit').onclick = async () => {
            const groups = document.querySelectorAll('#editBody .worker-row-group');

            let allValid = true;
            const data = Array.from(groups).map(group => {
                const rowData = {
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
                };

                const mandatoryFields = ['worker_name', 'passport', 'nationality', 'office', 'customer', 'national_id', 'guarantee_status', 'housing_location', 'entry_date', 'housing_entry_date', 'salary', 'status_description', 'action_type', 'ticket_info', 'settlement_status'];
                mandatoryFields.forEach(f => {
                    if (!rowData[f] || rowData[f] === '') allValid = false;
                });

                return rowData;
            });

            if (!allValid) return showToast('يرجى التأكد من تعبئة جميع الحقول لكل عاملة مختارة', 'error');

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
    }

    // Bulk Delete
    if (bulkDeleteBtn) {
        bulkDeleteBtn.onclick = async () => {
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
    }

    // Bulk Archive
    if (bulkArchiveBtn) {
        bulkArchiveBtn.onclick = async () => {
            const selectedIds = Array.from(document.querySelectorAll('.row-select:checked')).map(cb => cb.value);
            try {
                const response = await fetch('api.php?action=bulk_archive&status=1', {
                    method: 'POST',
                    body: JSON.stringify({ ids: selectedIds })
                });
                const result = await response.json();
                if (result.success) {
                    clearFilters();
                    showToast(`تم أرشفة ${selectedIds.length} عاملة بنجاح`, 'add');
                    fetchWorkers();
                } else {
                    showToast(result.message, 'error');
                }
            } catch (e) {
                showToast('حدث خطأ أثناء الأرشفة', 'error');
            }
        };
    }

    // Bulk Unarchive
    if (bulkUnarchiveBtn) {
        bulkUnarchiveBtn.onclick = async () => {
            const selectedIds = Array.from(document.querySelectorAll('.row-select:checked')).map(cb => cb.value);
            try {
                const response = await fetch('api.php?action=bulk_archive&status=0', {
                    method: 'POST',
                    body: JSON.stringify({ ids: selectedIds })
                });
                const result = await response.json();
                if (result.success) {
                    clearFilters();
                    showToast(`تم إلغاء أرشفة ${selectedIds.length} عاملة بنجاح`, 'add');
                    fetchWorkers();
                } else {
                    showToast(result.message, 'error');
                }
            } catch (e) {
                showToast('حدث خطأ أثناء إلغاء الأرشفة', 'error');
            }
        };
    }

    // Range Selection Logic (Automatic & Validated)
    const handleRangeSelect = () => {
        let from = parseInt(document.getElementById('rangeFrom').value);
        let to = parseInt(document.getElementById('rangeTo').value);

        if (isNaN(from) || isNaN(to) || from < 1 || from > to) {
            // If invalid, we don't clear or select anything automatically to avoid confusion
            return;
        }

        const checkboxes = document.querySelectorAll('.row-select');
        checkboxes.forEach((cb, index) => {
            const rowNum = index + 1;
            cb.checked = (rowNum >= from && rowNum <= to);
        });
        updateSelectionState();
    };

    if (document.getElementById('rangeFrom')) {
        document.getElementById('rangeFrom').addEventListener('input', handleRangeSelect);
        document.getElementById('rangeTo').addEventListener('input', handleRangeSelect);
    }

    // PDF Export (Smart Filtered)
    if (document.getElementById('exportPdfBtn')) {
        document.getElementById('exportPdfBtn').onclick = () => {
            const selected = document.querySelectorAll('.row-select:checked');
            
            if (selected.length > 0) {
                // Hide unselected rows
                document.querySelectorAll('.row-select').forEach(cb => {
                    if (!cb.checked) {
                        cb.closest('tr').classList.add('hide-for-print');
                    }
                });
                
                window.print();
                
                // Restore rows immediately after print trigger
                document.querySelectorAll('.hide-for-print').forEach(tr => {
                    tr.classList.remove('hide-for-print');
                });
            } else {
                // No selection, print everything as normal
                window.print();
            }
        };
    }

    fetchWorkers();
    fetchOffices();
});

// Toast System
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
