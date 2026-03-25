// assets/js/leave-summary.js
// All action functions are plain globals so onclick="fnName(id)" always resolves.

// ── Config ─────────────────────────────────────────────────────────────────
var LR_BASE = '/admin/api/leave-requests';
var LR_CSRF = ''; // filled on DOMContentLoaded

// ── Tab state ───────────────────────────────────────────────────────────────
var lrState = {
  pending: { year: new Date().getFullYear(), loaded: false },
  approved: { year: new Date().getFullYear(), loaded: false },
  disapproved: { year: new Date().getFullYear(), loaded: false }
};

// ── Utility ─────────────────────────────────────────────────────────────────
function lrColSpan(tab) {
  return tab === 'disapproved' ? 9 : 8;
}
function lrTbody(tab) {
  return document.getElementById(tab + '-tbody');
}
function lrBadge(tab) {
  return document.getElementById('badge-' + tab);
}

function lrEscHtml(s) {
  return String(s == null ? '' : s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function lrFmtDate(s) {
  if (!s) return '—';
  var d = new Date(s);
  return isNaN(d) ? s : d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

function lrDateRange(a, b) {
  if (!a && !b) return '—';
  if (!b || a === b) return lrFmtDate(a);
  return lrFmtDate(a) + ' – ' + lrFmtDate(b);
}

function lrUcFirst(s) {
  if (!s) return '—';
  return s.charAt(0).toUpperCase() + s.slice(1).replace(/_/g, ' ');
}

var LR_STATUS_CLS = { pending: 'bg-label-warning', approved: 'bg-label-success', disapproved: 'bg-label-danger' };
function lrStatusBadge(status) {
  return (
    '<span class="badge rounded-pill ' +
    (LR_STATUS_CLS[status] || 'bg-label-secondary') +
    '">' +
    lrUcFirst(status) +
    '</span>'
  );
}

// Get employee name from the row's data attribute at click time
function lrGetEmpName(id) {
  var tr = document.querySelector('tr[data-id="' + id + '"]');
  return tr && tr.dataset.empName ? tr.dataset.empName : 'this employee';
}

// ── State rows ───────────────────────────────────────────────────────────────
function lrSetLoading(tab) {
  var el = lrTbody(tab);
  if (!el) return;
  el.innerHTML =
    '<tr><td colspan="' +
    lrColSpan(tab) +
    '" class="text-center text-muted py-5">' +
    '<i class="ri ri-loader-4-line fs-3 d-block mb-1 opacity-50 spin"></i>Loading…</td></tr>';
}

function lrSetEmpty(tab, msg) {
  var el = lrTbody(tab);
  if (!el) return;
  el.innerHTML =
    '<tr><td colspan="' +
    lrColSpan(tab) +
    '" class="text-center text-muted py-5">' +
    '<i class="ri ri-inbox-line fs-3 d-block mb-1 opacity-40"></i>' +
    (msg || 'No records found.') +
    '</td></tr>';
}

function lrSetError(tab, msg) {
  var el = lrTbody(tab);
  if (!el) return;
  el.innerHTML =
    '<tr><td colspan="' +
    lrColSpan(tab) +
    '" class="text-center text-danger py-5">' +
    '<i class="ri ri-error-warning-line fs-3 d-block mb-1"></i>' +
    lrEscHtml(msg || 'Failed to load data.') +
    '</td></tr>';
}

// ── Action cell ──────────────────────────────────────────────────────────────
//
//  Pending rows  → View  +  Change Remark dropdown (⋮)
//                           Approve / Set Pending / Disapprove / ─── / Delete
//
//  Approved /
//  Disapproved   → View  +  Delete
//
//  onclick calls are plain global function names — no namespace needed.
//  Only the integer row.id appears in onclick — never employee names.

function lrActionCell(row) {
  var id = row.id;

  var viewBtn =
    '<button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill"' +
    ' title="View Details" onclick="viewLeave(' +
    id +
    ')">' +
    '<i class="icon-base ri ri-eye-line"></i></button>';

  if (row.status === 'pending') {
    // Dropdown uses <ul>/<li> — Bootstrap 5 standard structure
    var ddId = 'dd-' + id;
    var menu =
      '<div class="dropdown">' +
      '<button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill"' +
      ' data-bs-toggle="dropdown" aria-expanded="false" id="' +
      ddId +
      '"' +
      ' title="Change Remark">' +
      '<i class="icon-base ri ri-more-2-line"></i></button>' +
      '<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="' +
      ddId +
      '">' +
      '<li><a class="dropdown-item" href="javascript:void(0);" onclick="changeRemark(' +
      id +
      ",'approved')\">" +
      '<i class="icon-base ri ri-checkbox-circle-line me-2 text-success"></i>Approve</a></li>' +
      '<li><a class="dropdown-item" href="javascript:void(0);" onclick="changeRemark(' +
      id +
      ",'pending')\">" +
      '<i class="icon-base ri ri-time-line me-2 text-warning"></i>Set Pending</a></li>' +
      '<li><a class="dropdown-item" href="javascript:void(0);" onclick="openDisapprove(' +
      id +
      ')">' +
      '<i class="icon-base ri ri-close-circle-line me-2 text-danger"></i>Disapprove</a></li>' +
      '<li><hr class="dropdown-divider"></li>' +
      '<li><a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteLeave(' +
      id +
      ')">' +
      '<i class="icon-base ri ri-delete-bin-line me-2"></i>Delete</a></li>' +
      '</ul></div>';

    // Separate PDF button
    let pdfBtn =
      '<a href="/admin/api/leave-requests/' +
      id +
      '/pdf" target="_blank" ' +
      'class="btn btn-sm btn-icon btn-text-secondary rounded-pill" title="Download PDF">' +
      '<i class="icon-base ri ri-file-pdf-line"></i></a>';

    return '<div class="d-flex align-items-center justify-content-center gap-1">' + viewBtn + pdfBtn + menu + '</div>';
  }

  var deleteBtn =
    '<button type="button" class="btn btn-sm btn-icon btn-text-danger rounded-pill"' +
    ' title="Delete" onclick="deleteLeave(' +
    id +
    ')">' +
    '<i class="icon-base ri ri-delete-bin-line"></i></button>';

  return '<div class="d-flex align-items-center justify-content-center gap-1">' + viewBtn + deleteBtn + '</div>';
}

// ── Row builder ──────────────────────────────────────────────────────────────
function lrBuildRow(row, index, tab) {
  var tr = '<tr data-id="' + row.id + '" data-emp-name="' + lrEscHtml(row.employee_name || '') + '">';

  tr +=
    '<td class="text-muted" style="font-size:.8rem;">' +
    index +
    '</td>' +
    '<td><span class="fw-semibold" style="font-size:.875rem;">' +
    lrEscHtml(row.employee_name || '—') +
    '</span></td>' +
    '<td style="font-size:.83rem;">' +
    lrEscHtml(row.leave_type || '—') +
    '</td>' +
    '<td style="font-size:.83rem;">' +
    lrFmtDate(row.date_of_filing) +
    '</td>' +
    '<td style="font-size:.83rem;">' +
    lrDateRange(row.start_date, row.end_date) +
    '</td>' +
    '<td class="text-center fw-semibold" style="font-size:.875rem;">' +
    (row.total_days != null ? row.total_days : '—') +
    '</td>' +
    '<td class="text-center">' +
    lrStatusBadge(row.status) +
    '</td>';

  if (tab === 'disapproved') {
    tr +=
      '<td style="font-size:.8rem;color:#566a7f;max-width:160px;">' +
      (row.remarks ? lrEscHtml(row.remarks) : '<span class="text-muted fst-italic">—</span>') +
      '</td>';
  }

  tr += '<td class="text-center">' + lrActionCell(row) + '</td></tr>';
  return tr;
}

// ── Bootstrap Dropdown init ──────────────────────────────────────────────────
// Bootstrap 5 does NOT auto-init components injected via innerHTML.
// Must be called after every innerHTML write that contains dropdown triggers.
function lrInitDropdowns(container) {
  container.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(function (btn) {
    try {
      var existing = bootstrap.Dropdown.getInstance(btn);
      if (existing) existing.dispose();
      new bootstrap.Dropdown(btn);
    } catch (e) {
      console.warn('[LeaveRequests] Dropdown init failed for btn:', btn, e);
    }
  });
}

// ── Tab loader ───────────────────────────────────────────────────────────────
function loadTab(tab, year) {
  lrState[tab].year = Number(year);
  lrState[tab].loaded = false;
  lrSetLoading(tab);

  fetch(LR_BASE + '?status=' + encodeURIComponent(tab) + '&year=' + year, {
    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': LR_CSRF }
  })
    .then(function (res) {
      return res.json().then(function (data) {
        if (!res.ok) throw new Error('[' + res.status + '] ' + (data.message || 'Server error'));
        return data;
      });
    })
    .then(function (data) {
      var rows = Array.isArray(data) ? data : data.data || [];
      var b = lrBadge(tab);
      if (b) b.textContent = rows.length;

      if (!rows.length) {
        lrSetEmpty(
          tab,
          {
            pending: 'No pending requests for ' + year + '.',
            approved: 'No approved requests for ' + year + '.',
            disapproved: 'No disapproved requests for ' + year + '.'
          }[tab]
        );
        return;
      }

      var el = lrTbody(tab);
      if (!el) return;

      el.innerHTML = rows
        .map(function (row, i) {
          return lrBuildRow(row, i + 1, tab);
        })
        .join('');

      lrInitDropdowns(el);
      lrState[tab].loaded = true;
    })
    .catch(function (err) {
      console.error('[LeaveRequests] loadTab(' + tab + ') failed:', err);
      lrSetError(tab, err.message);
    });
}

// ── Reload helper ────────────────────────────────────────────────────────────
function lrReloadAll() {
  loadTab('pending', lrState.pending.year);
  if (lrState.approved.loaded) loadTab('approved', lrState.approved.year);
  if (lrState.disapproved.loaded) loadTab('disapproved', lrState.disapproved.year);
}

// ── VIEW ─────────────────────────────────────────────────────────────────────
function viewLeave(id) {
  var modalEl = document.getElementById('modal-view-details');
  var skeleton = document.getElementById('modal-detail-skeleton');
  var bodyEl = document.getElementById('modal-detail-body');
  var footer = document.getElementById('modal-detail-footer');

  // Guard: if the modal HTML is missing from the DOM, fail visibly
  if (!modalEl || !skeleton || !bodyEl || !footer) {
    console.error('[viewLeave] Modal elements not found in DOM. Check that view-modal.blade.php is included.');
    alert('View modal is not available. Please contact the administrator.');
    return;
  }

  skeleton.style.display = 'block';
  bodyEl.style.display = 'none';
  document.getElementById('modal-detail-title').textContent = 'Leave Request';
  document.getElementById('modal-detail-subtitle').textContent = '';

  // bootstrap.Modal.getOrCreate() requires Bootstrap ≥ 5.2.
  // Use new bootstrap.Modal() which works on all Bootstrap 5.x versions.
  var bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
  bsModal.show();

  fetch(LR_BASE + '/' + id, { headers: { Accept: 'application/json', 'X-CSRF-TOKEN': LR_CSRF } })
    .then(function (res) {
      if (!res.ok) throw new Error('HTTP ' + res.status);
      return res.json();
    })
    .then(function (data) {
      lrPopulateModal(data.data || data, footer);
      skeleton.style.display = 'none';
      bodyEl.style.display = 'block';
    })
    .catch(function (err) {
      console.error('[LeaveRequests] viewLeave(' + id + ') failed:', err);
      skeleton.innerHTML =
        '<div class="text-center text-danger py-4">' +
        '<i class="ri ri-error-warning-line fs-3 d-block mb-2"></i>Could not load details.</div>';
    });
}

function lrPopulateModal(r, footer) {
  document.getElementById('modal-detail-title').textContent = r.employee_name || 'Leave Request';
  document.getElementById('modal-detail-subtitle').textContent = r.employee_number ? '#' + r.employee_number : '';

  var initials = (r.employee_name || '?')
    .split(' ')
    .map(function (w) {
      return w[0] || '';
    })
    .slice(0, 2)
    .join('')
    .toUpperCase();
  document.getElementById('modal-emp-name').textContent = r.employee_name || '—';
  document.getElementById('modal-emp-meta').textContent = [r.position, r.office].filter(Boolean).join(' · ') || '—';
  document.getElementById('modal-status-badge').innerHTML = lrStatusBadge(r.status || 'pending');

  document.getElementById('d-leave-type').textContent = r.leave_type || '—';
  document.getElementById('d-date-filed').textContent = lrFmtDate(r.date_of_filing);
  document.getElementById('d-start-date').textContent = lrFmtDate(r.start_date);
  document.getElementById('d-end-date').textContent = lrFmtDate(r.end_date);
  document.getElementById('d-total-days').textContent = r.total_days ? r.total_days + ' day(s)' : '—';
  document.getElementById('d-manager-status').textContent = lrUcFirst(r.manager_status || 'pending');

  var causeWrap = document.getElementById('d-cause-wrap');
  document.getElementById('d-cause').textContent = r.cause || '';
  causeWrap.style.display = r.cause ? 'block' : 'none';

  var remarksWrap = document.getElementById('d-remarks-wrap');
  if (r.status === 'disapproved' && r.remarks) {
    document.getElementById('d-remarks').textContent = r.remarks;
    remarksWrap.style.display = 'block';
  } else {
    remarksWrap.style.display = 'none';
  }

  var tlEl = document.getElementById('d-timeline');
  if (tlEl) {
    if (Array.isArray(r.timeline) && r.timeline.length) {
      tlEl.innerHTML = r.timeline
        .map(function (step) {
          var cls =
            step.label && step.label.toLowerCase().includes('approved')
              ? 'tl-approved'
              : step.label && step.label.toLowerCase().includes('rejected')
                ? 'tl-rejected'
                : step.label && step.label.toLowerCase().includes('recommended')
                  ? 'tl-recommended'
                  : '';
          return (
            '<li class="' +
            cls +
            '">' +
            '<strong style="font-size:.83rem;">' +
            lrEscHtml(step.label || '') +
            '</strong>' +
            (step.date
              ? ' <span class="text-muted" style="font-size:.8rem;">— ' + lrFmtDate(step.date) + '</span>'
              : '') +
            (step.note ? '<br><small class="text-muted">' + lrEscHtml(step.note) + '</small>' : '') +
            '</li>'
          );
        })
        .join('');
    } else {
      tlEl.innerHTML =
        '<li style="padding:.5rem 0 .5rem 2rem;font-size:.83rem;" class="text-muted">No timeline events yet.</li>';
    }
  }

  footer.innerHTML =
    '<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">' +
    '<i class="ri ri-close-line me-1"></i>Close</button>';
}

// ── CHANGE REMARK ────────────────────────────────────────────────────────────
function changeRemark(id, remark) {
  var labels = { approved: 'Approve', pending: 'reset to Pending', rejected: 'Disapprove' };
  if (!confirm('Confirm: ' + (labels[remark] || remark) + ' this leave request?')) return;
  lrSendRemark(id, remark, null);
}

// ── DISAPPROVE (with reason modal) ───────────────────────────────────────────
function openDisapprove(id) {
  document.getElementById('disapprove-target-id').value = id;
  document.getElementById('disapprove-emp-name').textContent = lrGetEmpName(id);
  document.getElementById('disapprove-reason').value = '';
  var disapproveEl = document.getElementById('modal-disapprove');
  var bsDisapprove = bootstrap.Modal.getInstance(disapproveEl) || new bootstrap.Modal(disapproveEl);
  bsDisapprove.show();
}

function submitDisapprove() {
  var id = document.getElementById('disapprove-target-id').value;
  var reason = document.getElementById('disapprove-reason').value.trim();
  var btn = document.getElementById('btn-confirm-disapprove');
  if (!id) return;

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving…';

  // URLSearchParams required for PATCH — Laravel does not parse FormData on non-POST requests
  var body = new URLSearchParams();
  body.append('remark', 'rejected');
  if (reason) body.append('reason', reason);

  fetch(LR_BASE + '/' + id + '/remark', {
    method: 'PATCH',
    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': LR_CSRF },
    body: body
  })
    .then(function (res) {
      return res.json().then(function (d) {
        if (!res.ok) throw new Error(d.message || 'HTTP ' + res.status);
        return d;
      });
    })
    .then(function (d) {
      btn.disabled = false;
      btn.innerHTML = '<i class="ri ri-close-circle-line me-1"></i>Confirm Disapprove';
      lrShowToast(d.message || 'Leave disapproved.', 'warning');
      bootstrap.Modal.getInstance(document.getElementById('modal-disapprove'))?.hide();
      lrReloadAll();
    })
    .catch(function (err) {
      btn.disabled = false;
      btn.innerHTML = '<i class="ri ri-close-circle-line me-1"></i>Confirm Disapprove';
      lrShowToast(err.message || 'Could not disapprove. Please try again.', 'danger');
    });
}

// ── DELETE ────────────────────────────────────────────────────────────────────
function deleteLeave(id) {
  if (!confirm('Delete the leave request of ' + lrGetEmpName(id) + '? This cannot be undone.')) return;

  fetch(LR_BASE + '/' + id, {
    method: 'DELETE',
    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': LR_CSRF }
  })
    .then(function (res) {
      return res.json().then(function (d) {
        if (!res.ok) throw new Error(d.message || 'HTTP ' + res.status);
        return d;
      });
    })
    .then(function (d) {
      lrShowToast(d.message || 'Request deleted.', 'success');
      lrReloadAll();
    })
    .catch(function (err) {
      lrShowToast(err.message || 'Could not delete. Please try again.', 'danger');
    });
}

// ── Shared remark sender ─────────────────────────────────────────────────────
// Uses URLSearchParams (application/x-www-form-urlencoded) because Laravel
// does not parse multipart/FormData bodies on PATCH requests.
function lrSendRemark(id, remark, reason) {
  var body = new URLSearchParams();
  body.append('remark', remark);
  if (reason) body.append('reason', reason);

  fetch(LR_BASE + '/' + id + '/remark', {
    method: 'PATCH',
    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': LR_CSRF },
    body: body
  })
    .then(function (res) {
      return res.json().then(function (d) {
        if (!res.ok) throw new Error(d.message || 'HTTP ' + res.status);
        return d;
      });
    })
    .then(function (d) {
      var type = remark === 'approved' ? 'success' : 'warning';
      lrShowToast(d.message || 'Updated.', type);
      lrReloadAll();
    })
    .catch(function (err) {
      lrShowToast(err.message || 'Something went wrong.', 'danger');
    });
}

// ── Toast ─────────────────────────────────────────────────────────────────────
function lrShowToast(message, type) {
  var container = document.getElementById('toastContainer');
  if (!container) return;
  var id = 'toast-' + Date.now();
  var icons = { success: 'ri-checkbox-circle-line', danger: 'ri-error-warning-line', warning: 'ri-alert-line' };

  container.insertAdjacentHTML(
    'beforeend',
    '<div id="' +
      id +
      '" class="toast align-items-center text-white border-0 bg-' +
      type +
      '"' +
      ' role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">' +
      '<div class="d-flex"><div class="toast-body d-flex align-items-center gap-2">' +
      '<i class="ri ' +
      (icons[type] || 'ri-information-line') +
      ' fs-5 flex-shrink-0"></i>' +
      lrEscHtml(message) +
      '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
      '</div></div>'
  );
  var el = document.getElementById(id);
  new bootstrap.Toast(el).show();
  el.addEventListener('hidden.bs.toast', function () {
    el.remove();
  });
}

// ── Init ──────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
  // Read CSRF here so the meta tag is guaranteed to exist
  LR_CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

  loadTab('pending', lrState.pending.year);

  // Lazy-load approved and disapproved tabs on first switch
  ['approved', 'disapproved'].forEach(function (tab) {
    var btn = document.getElementById('tab-' + tab);
    if (!btn) return;
    btn.addEventListener('shown.bs.tab', function () {
      if (!lrState[tab].loaded) loadTab(tab, lrState[tab].year);
    });
  });
});

// ── Public object for blade year-select onchange + modal button calls ────────
var leaveRequests = {
  loadTab: loadTab,
  submitDisapprove: submitDisapprove
};
