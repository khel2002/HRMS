'use strict';

/* ═══════════════════════════════════════════════════════════
   CONSTANTS & STATE
═══════════════════════════════════════════════════════════ */
const WORKING_DAYS_PER_YEAR = 260;
const MONTHS_PER_YEAR = 12;

const CFG = window.SalaryConfig || {};
let recurringItems = (CFG.recurringItems || []).map(normalizeRecurringItem);
let nextTempId = -(recurringItems.length + 1); // negative = not yet persisted
let editingId = null;

/* ═══════════════════════════════════════════════════════════
   DATA NORMALISATION
═══════════════════════════════════════════════════════════ */
function normalizeRecurringItem(item) {
  return {
    id: item.id ?? null,
    type: item.type ?? 'Earning',
    category_code: item.category_code ?? item.categoryCode ?? '',
    amount: parseFloat(item.amount || 0),
    applyOn: item.applyOn ?? item.apply_on ?? 'Both Cutoffs',
    active: item.active ?? item.is_active ?? true
  };
}

/**
 * Convert a snake_case category code to a human-readable display label.
 * Mirrors PayslipService::humanizeCategoryCode() in PHP.
 */
function categoryCodeToLabel(code) {
  code = String(code || '').trim();
  if (!code) return '—';

  const acronyms = ['SSS', 'HDMF', 'FB'];

  return code
    .split(/_+/)
    .filter(Boolean)
    .map(part => {
      const u = part.toUpperCase();
      if (acronyms.includes(u)) return u;
      if (u === 'PAGIBIG') return 'PAG-IBIG';
      return part.charAt(0).toUpperCase() + part.slice(1).toLowerCase();
    })
    .join(' ');
}

/* ═══════════════════════════════════════════════════════════
   FORMATTING HELPERS
═══════════════════════════════════════════════════════════ */
function fmtPeso(val) {
  return (
    '₱' +
    Number(val || 0).toLocaleString('en-PH', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    })
  );
}

function fmtNum(val) {
  return Number(val || 0).toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
}

function fmtDate(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr + 'T00:00:00');
  if (isNaN(d)) return dateStr;
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function escHtml(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

/* ═══════════════════════════════════════════════════════════
   RATE CALCULATION  (hourly rate removed per policy)
═══════════════════════════════════════════════════════════ */
function calcRates(monthly) {
  const m = parseFloat(monthly) || 0;
  return {
    semiMonthly: m / 2,
    daily: (m * MONTHS_PER_YEAR) / WORKING_DAYS_PER_YEAR,
    annual: m * MONTHS_PER_YEAR
  };
}

function updateRates() {
  const monthly = parseFloat($('#monthlyRate').val()) || 0;
  const rates = calcRates(monthly);

  $('#semiMonthlyRate').val(fmtNum(rates.semiMonthly));
  $('#dailyRate').val(fmtNum(rates.daily));

  $('#prev-monthly').text(fmtPeso(monthly));
  $('#prev-cutoff').text(fmtPeso(rates.semiMonthly));
  $('#prev-daily').text(fmtPeso(rates.daily));
  $('#prev-annual').text(fmtPeso(rates.annual));
}

function updatePreviewConfig() {
  $('#prev-type').text($('#payrollType option:selected').text() || '—');
  $('#prev-freq').text($('#paymentFrequency option:selected').text() || '—');
  $('#prev-date').text(fmtDate($('#effectiveDate').val()));
}

/* ═══════════════════════════════════════════════════════════
   EMPLOYEE UI
═══════════════════════════════════════════════════════════ */
function setEmployeeUI(employee) {
  $('#employeeId').val(employee.employee_number ?? '—');
  $('#department').val(employee.department ?? '—');
  $('#position').val(employee.position ?? '—');
  $('#dateHired').val(employee.date_hired ?? '—');
  $('#empAvatar').text(employee.avatar ?? '--');

  const active = String(employee.status ?? '').toLowerCase() === 'active';
  $('#empStatusBadge')
    .text(active ? 'Active' : 'Inactive')
    .removeClass('ss-status-active ss-status-inactive')
    .addClass(active ? 'ss-status-active' : 'ss-status-inactive');
}

function setSalaryUI(salary) {
  const today = new Date().toISOString().split('T')[0];

  if (!salary) {
    $('#monthlyRate').val('');
    $('#semiMonthlyRate').val('');
    $('#dailyRate').val('');
    $('#effectiveDate').val(today);
    // FIX: blade option values use underscore — 'semi_monthly' not 'semi-monthly'
    $('#payrollType').val('semi_monthly');
    $('#paymentFrequency').val('every_cutoff');
    $('#statusToggle').prop('checked', true);
    $('#statusLabel').text('Active');
    updateRates();
    updatePreviewConfig();
    return;
  }

  $('#monthlyRate').val(salary.monthly_rate ?? '');
  $('#semiMonthlyRate').val(salary.semi_monthly_rate ?? '');
  $('#dailyRate').val(salary.daily_rate ?? '');
  $('#effectiveDate').val(salary.effective_date ?? today);

  // FIX: normalise both underscore and hyphen variants to what the <option> uses.
  const payrollType = (salary.payroll_type ?? 'semi_monthly').replace('-', '_');
  const payFreq = (salary.payment_frequency ?? 'every_cutoff').replace('-', '_');
  $('#payrollType').val(payrollType);
  $('#paymentFrequency').val(payFreq);

  $('#statusToggle').prop('checked', !!salary.is_active);
  $('#statusLabel').text(salary.is_active ? 'Active' : 'Inactive');

  updateRates();
  updatePreviewConfig();
}

function populateEmployeeInfoFromOption() {
  const $sel = $('#selectEmployee');
  const $selected = $sel.find('option:selected');
  const selectedId = $sel.val();

  if (!selectedId) {
    $('#employeeId, #department, #position, #dateHired').val('');
    $('#empAvatar').text('--');
    $('#empStatusBadge').text('—').removeClass('ss-status-active ss-status-inactive').addClass('ss-status-inactive');
    return;
  }

  const status = String($selected.data('status') || '').toLowerCase();
  const active = status === 'active';

  $('#employeeId').val($selected.data('employee-number') || '');
  $('#department').val($selected.data('dept') || '—');
  $('#position').val($selected.data('pos') || '—');
  $('#dateHired').val($selected.data('hired') || '—');
  $('#empAvatar').text($selected.data('avatar') || '--');

  $('#empStatusBadge')
    .text(active ? 'Active' : 'Inactive')
    .removeClass('ss-status-active ss-status-inactive')
    .addClass(active ? 'ss-status-active' : 'ss-status-inactive');
}

/* ═══════════════════════════════════════════════════════════
   FETCH EMPLOYEE SETTINGS FROM SERVER
═══════════════════════════════════════════════════════════ */
function fetchEmployeeSettings(employeeId) {
  const showUrl = (CFG.routes?.show || '').replace('__EMPLOYEE__', employeeId);
  if (!showUrl || showUrl.includes('__EMPLOYEE__')) {
    showToast('Salary settings routes are not configured on this page.', 'error');
    return;
  }

  $.ajax({
    url: showUrl,
    type: 'GET',
    dataType: 'json',
    success(response) {
      const data = response.data || {};
      setEmployeeUI(data.employee || {});
      setSalaryUI(data.salary || null);

      recurringItems = (data.recurring_items || []).map(normalizeRecurringItem);
      nextTempId = -(recurringItems.length + 1);
      renderTable();
    },
    error() {
      showToast('Failed to load employee salary settings.', 'error');
    }
  });
}

/* ═══════════════════════════════════════════════════════════
   RECURRING ITEMS TABLE
═══════════════════════════════════════════════════════════ */
function renderTable() {
  const $tbody = $('#recurringBody');
  $tbody.empty();

  if (!recurringItems.length) {
    $tbody.html(`
      <tr>
        <td colspan="6" class="text-center py-4 text-muted" style="font-size:13px;">
          No recurring items yet. Click <strong>+ Add Item</strong> to get started.
        </td>
      </tr>
    `);
    return;
  }

  recurringItems.forEach(function (item) {
    const isDeduction = item.type === 'Deduction';
    const label = categoryCodeToLabel(item.category_code);

    $tbody.append(`
      <tr data-id="${escHtml(String(item.id ?? ''))}">
        <td>
          <span class="ss-badge ${isDeduction ? 'ss-badge-deduction' : 'ss-badge-earning'}">
            ${escHtml(item.type)}
          </span>
        </td>
        <td>
          <div class="ss-item-name">
            <i class="ri ri-price-tag-3-line"></i>
            ${escHtml(label)}
          </div>
        </td>
        <td class="text-end ss-amount">₱ ${fmtNum(item.amount)}</td>
        <td><span class="ss-badge-apply">${escHtml(item.applyOn)}</span></td>
        <td>
          <label class="ss-toggle">
            <input type="checkbox" class="item-toggle"
              data-id="${escHtml(String(item.id ?? ''))}" ${item.active ? 'checked' : ''}>
            <span class="ss-slider"></span>
          </label>
        </td>
        <td>
          <div class="ss-action-btns">
            <button type="button" class="ss-btn-action edit"
              data-id="${escHtml(String(item.id ?? ''))}" title="Edit">
              <i class="ri ri-pencil-line"></i>
            </button>
            <button type="button" class="ss-btn-action del"
              data-id="${escHtml(String(item.id ?? ''))}" title="Delete">
              <i class="ri ri-delete-bin-line"></i>
            </button>
          </div>
        </td>
      </tr>
    `);
  });
}

/* ═══════════════════════════════════════════════════════════
   RECURRING ITEM MODAL
═══════════════════════════════════════════════════════════ */
function resetModal() {
  editingId = null;
  $('#modalTitle').text('Add Recurring Item');
  $('#itemType').val('Earning');
  $('#itemCategory').val($('#itemCategory option:first').val());
  $('#itemAmount').val('');
  $('#itemApplyOn').val('Both Cutoffs');
  $('#itemStatus').prop('checked', true);
  $('#itemStatusLabel').text('Active');
  clearModalErrors();
}

function clearModalErrors() {
  $('#recurringItemModal .ss-field-error').remove();
  $('#recurringItemModal .ss-input').removeClass('is-invalid');
}

function validateModal() {
  clearModalErrors();
  let valid = true;

  const amount = parseFloat($('#itemAmount').val());
  if (!$('#itemAmount').val().trim() || isNaN(amount) || amount <= 0) {
    const $el = $('#itemAmount');
    $el.addClass('is-invalid');
    $el.closest('.col-6').append('<span class="ss-field-error">Amount must be greater than 0.</span>');
    valid = false;
  }

  return valid;
}

function saveModalItem() {
  if (!validateModal()) return;

  const payload = {
    type: $('#itemType').val(),
    category_code: $('#itemCategory').val(),
    amount: parseFloat($('#itemAmount').val()),
    applyOn: $('#itemApplyOn').val(),
    active: $('#itemStatus').is(':checked')
  };

  if (editingId !== null) {
    const idx = recurringItems.findIndex(i => String(i.id) === String(editingId));
    if (idx > -1) {
      recurringItems[idx] = { ...recurringItems[idx], ...payload };
    }
    showToast('Recurring item updated.', 'success');
  } else {
    recurringItems.push({ id: nextTempId--, ...payload });
    showToast('Recurring item added.', 'success');
  }

  bootstrap.Modal.getOrCreateInstance(document.getElementById('recurringItemModal')).hide();
  renderTable();
}

/* ═══════════════════════════════════════════════════════════
   PAYLOAD BUILDER
═══════════════════════════════════════════════════════════ */
function parseRate(selector) {
  return parseFloat(String($(selector).val()).replace(/,/g, '')) || 0;
}

function buildPayload() {
  return {
    employee_id: $('#selectEmployee').val(),
    monthly_rate: parseFloat($('#monthlyRate').val()) || 0,
    semi_monthly_rate: parseRate('#semiMonthlyRate'),
    daily_rate: parseRate('#dailyRate'),
    payroll_type: $('#payrollType').val(),
    payment_frequency: $('#paymentFrequency').val(),
    effective_date: $('#effectiveDate').val(),
    is_active: $('#statusToggle').is(':checked') ? 1 : 0,
    recurring_items: recurringItems.map(item => ({
      // Send null for unsaved items (negative temp ids) so the server creates them.
      id: item.id !== null && Number(item.id) > 0 ? item.id : null,
      type: item.type,
      category_code: item.category_code,
      amount: item.amount,
      applyOn: item.applyOn,
      active: item.active
    })),
    _token: CFG.routes?.csrf || ''
  };
}

/* ═══════════════════════════════════════════════════════════
   SAVE / RESET
═══════════════════════════════════════════════════════════ */
function saveSettings() {
  if (!$('#selectEmployee').val()) {
    showToast('Please select an employee first.', 'error');
    return;
  }

  const monthly = parseFloat($('#monthlyRate').val());
  if (!monthly || monthly <= 0) {
    $('#monthlyRate').addClass('is-invalid').focus();
    showToast('Please enter a valid Monthly Rate.', 'error');
    return;
  }

  $('#monthlyRate').removeClass('is-invalid');

  const $btn = $('#btnSave');
  $btn.prop('disabled', true).html('<i class="ri ri-loader-4-line me-1"></i> Saving…');

  $.ajax({
    url: CFG.routes?.store || '#',
    type: 'POST',
    data: JSON.stringify(buildPayload()),
    contentType: 'application/json',
    headers: {
      'X-CSRF-TOKEN': CFG.routes?.csrf || '',
      Accept: 'application/json'
    },
    success(response) {
      showToast(response.message || 'Salary settings saved successfully!', 'success');
      fetchEmployeeSettings($('#selectEmployee').val());
    },
    error(xhr) {
      if (xhr.status === 422 && xhr.responseJSON?.errors) {
        const errors = xhr.responseJSON.errors;
        const firstKey = Object.keys(errors)[0];
        showToast(errors[firstKey]?.[0] || 'Validation failed.', 'error');
        return;
      }
      showToast(xhr.responseJSON?.message || 'Failed to save salary settings.', 'error');
    },
    complete() {
      $btn.prop('disabled', false).html('<i class="ri-save-2-line me-1"></i> Save Salary Settings');
    }
  });
}

function resetForm() {
  const employeeId = $('#selectEmployee').val();
  if (!employeeId) return;
  fetchEmployeeSettings(employeeId);
  showToast('Form has been reset.', 'success');
}

/* ═══════════════════════════════════════════════════════════
   TOAST
═══════════════════════════════════════════════════════════ */
function showToast(msg, type = 'success') {
  if (window.toastr) {
    window.toastr[type === 'error' ? 'error' : 'success'](msg);
    return;
  }

  const container =
    document.getElementById('ssToastContainer') ||
    document.getElementById('toastContainer') ||
    document.querySelector('.toast-container');

  if (!container || !window.bootstrap?.Toast) {
    alert(msg);
    return;
  }

  const variant = type === 'error' ? 'danger' : 'success';
  const title = type === 'error' ? 'Error' : 'Success';
  const icon = type === 'error' ? 'ri-error-warning-line' : 'ri-check-circle-line';

  const el = document.createElement('div');
  el.className = `toast align-items-center text-white bg-${variant} border-0`;
  el.setAttribute('role', 'alert');
  el.setAttribute('aria-live', 'assertive');
  el.setAttribute('aria-atomic', 'true');
  el.dataset.bsDelay = '3500';
  el.innerHTML = `
    <div class="d-flex">
      <div class="toast-body d-flex align-items-center gap-2">
        <i class="ri ${icon}"></i>
        <strong class="me-1">${title}:</strong>
        <span>${escHtml(msg)}</span>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto"
        data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  `;

  container.appendChild(el);
  const toast = new bootstrap.Toast(el);
  toast.show();
  el.addEventListener('hidden.bs.toast', () => el.remove());
}

/* ═══════════════════════════════════════════════════════════
   DOM READY
═══════════════════════════════════════════════════════════ */
$(function () {
  populateEmployeeInfoFromOption();
  updateRates();
  updatePreviewConfig();
  renderTable();

  // Employee selector.
  $('#selectEmployee').on('change', function () {
    const employeeId = $(this).val();
    populateEmployeeInfoFromOption();
    if (employeeId) fetchEmployeeSettings(employeeId);
  });

  // Rate / config inputs.
  $('#monthlyRate').on('input', updateRates);
  $('#payrollType, #paymentFrequency, #effectiveDate').on('change', updatePreviewConfig);

  // Main status toggle.
  $('#statusToggle').on('change', function () {
    $('#statusLabel').text($(this).is(':checked') ? 'Active' : 'Inactive');
  });

  // Modal status toggle.
  $('#itemStatus').on('change', function () {
    $('#itemStatusLabel').text($(this).is(':checked') ? 'Active' : 'Inactive');
  });

  // Open add-item modal.
  $('#btnAddItem').on('click', function () {
    resetModal();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('recurringItemModal')).show();
  });

  // Reset modal state on close.
  $('#recurringItemModal').on('hidden.bs.modal', resetModal);

  // Save from modal.
  $('#modalSave').on('click', saveModalItem);

  // Edit row.
  $('#recurringBody').on('click', '.edit', function () {
    const id = String($(this).data('id'));
    const item = recurringItems.find(i => String(i.id) === id);
    if (!item) return;

    editingId = item.id;
    $('#modalTitle').text('Edit Recurring Item');
    $('#itemType').val(item.type);
    $('#itemCategory').val(item.category_code);
    $('#itemAmount').val(item.amount);
    $('#itemApplyOn').val(item.applyOn);
    $('#itemStatus').prop('checked', !!item.active);
    $('#itemStatusLabel').text(item.active ? 'Active' : 'Inactive');

    bootstrap.Modal.getOrCreateInstance(document.getElementById('recurringItemModal')).show();
  });

  // Delete row.
  $('#recurringBody').on('click', '.del', function () {
    const id = String($(this).data('id'));
    if (!confirm('Delete this recurring item?')) return;

    recurringItems = recurringItems.filter(i => String(i.id) !== id);
    renderTable();
    showToast('Item removed. Save settings to apply changes.', 'success');
  });

  // Inline active toggle.
  $('#recurringBody').on('change', '.item-toggle', function () {
    const id = String($(this).data('id'));
    const item = recurringItems.find(i => String(i.id) === id);
    if (item) item.active = $(this).is(':checked');
  });

  // Save & reset buttons.
  $('#btnSave').on('click', saveSettings);
  $('#btnReset').on('click', resetForm);
});
