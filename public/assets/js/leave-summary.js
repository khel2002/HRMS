(function () {
  // ── Config ──────────────────────────────────────────────────────────────────
  // Adjust the URL to your actual API routes.
  const API = {
    list: '/admin/api/leave-requests', // GET  ?status=pending&year=2026
    delete: '/admin/api/leave-requests', // DELETE /{id}
    view: '/admin/api/leave-requests' // GET  /{id}
  };

  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

  // ── State ────────────────────────────────────────────────────────────────────
  // Track which year is active per tab so year-select changes only reload that tab.
  const state = {
    pending: {
      year: new Date().getFullYear(),
      loaded: false
    },
    approved: {
      year: new Date().getFullYear(),
      loaded: false
    },
    disapproved: {
      year: new Date().getFullYear(),
      loaded: false
    }
  };

  // ── Helpers ──────────────────────────────────────────────────────────────────

  /** Return the <tbody> element for a given tab key. */
  function tbody(tab) {
    return document.getElementById(tab + '-tbody');
  }

  /** Return the badge element for a given tab key. */
  function badge(tab) {
    return document.getElementById('badge-' + tab);
  }

  /** Render a spinner/loading row into a tbody. */
  function setLoading(tab, colSpan) {
    const el = tbody(tab);
    if (!el) return;
    el.innerHTML =
      '<tr><td colspan="' +
      colSpan +
      '" class="text-center text-muted py-4">' +
      '<i class="ri ri-loader-4-line fs-4 d-block mb-1 opacity-50 spin"></i>' +
      'Loading…</td></tr>';
  }

  /** Render an empty-state row. */
  function setEmpty(tab, colSpan, message) {
    const el = tbody(tab);
    if (!el) return;
    el.innerHTML =
      '<tr><td colspan="' +
      colSpan +
      '" class="text-center text-muted py-4">' +
      '<i class="ri ri-inbox-line fs-4 d-block mb-1 opacity-50"></i>' +
      (message || 'No records found') +
      '</td></tr>';
  }

  /** Render an error row. */
  function setError(tab, colSpan, message) {
    const el = tbody(tab);
    if (!el) return;
    el.innerHTML =
      '<tr><td colspan="' +
      colSpan +
      '" class="text-center text-danger py-4">' +
      '<i class="ri ri-error-warning-line fs-4 d-block mb-1"></i>' +
      (message || 'Failed to load data.') +
      '</td></tr>';
  }

  /** Format a date string to a readable format. */
  function fmtDate(str) {
    if (!str) return '—';
    const d = new Date(str);
    return isNaN(d)
      ? str
      : d.toLocaleDateString('en-PH', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });
  }

  /** Capitalise first letter. */
  function ucFirst(str) {
    if (!str) return '—';
    return str.charAt(0).toUpperCase() + str.slice(1).replace(/_/g, ' ');
  }

  // ── Status badge HTML ─────────────────────────────────────────────────────────
  const STATUS_CLASSES = {
    pending: 'bg-warning text-dark',
    approved: 'bg-success',
    disapproved: 'bg-danger',
    cancelled: 'bg-secondary'
  };

  function statusBadge(status) {
    const cls = STATUS_CLASSES[status] ?? 'bg-secondary';
    return '<span class="badge rounded-pill ' + cls + '">' + ucFirst(status) + '</span>';
  }

  // ── Row builders ─────────────────────────────────────────────────────────────

  /** Build a row for pending / approved tables (6 columns). */
  function buildRow6(row, index) {
    return (
      '<tr>' +
      '<td class="text-muted" style="font-size:.8rem;">' +
      index +
      '</td>' +
      '<td>' +
      '<span class="fw-semibold" style="font-size:.85rem;">' +
      ucFirst(row.leave_type) +
      '</span>' +
      '</td>' +
      '<td style="font-size:.83rem;">' +
      fmtDate(row.date_of_filing) +
      '</td>' +
      '<td style="font-size:.83rem;">' +
      (row.total_days ?? '—') +
      ' day(s)</td>' +
      '<td class="text-center">' +
      statusBadge(row.status) +
      '</td>' +
      '<td class="text-center">' +
      '<div class="d-flex justify-content-center gap-1">' +
      '<button class="btn btn-sm btn-icon btn-text-secondary rounded-pill"' +
      ' title="View" onclick="leaveRequests.openModal(' +
      row.id +
      ')">' +
      '<i class="ri ri-eye-line"></i>' +
      '</button>' +
      (row.status === 'pending'
        ? '<button class="btn btn-sm btn-icon btn-text-danger rounded-pill"' +
          ' title="Delete" onclick="leaveRequests.confirmDelete(' +
          row.id +
          ')">' +
          '<i class="ri ri-delete-bin-line"></i>' +
          '</button>'
        : '') +
      '</div>' +
      '</td>' +
      '</tr>'
    );
  }

  /** Build a row for the disapproved table (7 columns, adds Comment). */
  function buildRow7(row, index) {
    return (
      '<tr>' +
      '<td class="text-muted" style="font-size:.8rem;">' +
      index +
      '</td>' +
      '<td>' +
      '<span class="fw-semibold" style="font-size:.85rem;">' +
      ucFirst(row.leave_type) +
      '</span>' +
      '</td>' +
      '<td style="font-size:.83rem;">' +
      fmtDate(row.date_of_filing) +
      '</td>' +
      '<td style="font-size:.83rem;">' +
      (row.total_days ?? '—') +
      ' day(s)</td>' +
      '<td class="text-center">' +
      statusBadge(row.status) +
      '</td>' +
      '<td style="font-size:.8rem;color:#555;">' +
      (row.remarks ? escHtml(row.remarks) : '<span class="text-muted">—</span>') +
      '</td>' +
      '<td class="text-center">' +
      '<button class="btn btn-sm btn-icon btn-text-secondary rounded-pill"' +
      ' title="View" onclick="leaveRequests.openModal(' +
      row.id +
      ')">' +
      '<i class="ri ri-eye-line"></i>' +
      '</button>' +
      '</td>' +
      '</tr>'
    );
  }

  /** Minimal HTML escaping to prevent XSS in remarks. */
  function escHtml(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  // ── Core loader ───────────────────────────────────────────────────────────────

  /**
   * Fetch and render rows for a tab.
   * @param {string} tab    'pending' | 'approved' | 'disapproved'
   * @param {number} year   e.g. 2026
   */
  function loadTab(tab, year) {
    state[tab].year = Number(year);
    state[tab].loaded = false;

    const colSpan = tab === 'disapproved' ? 7 : 6;
    setLoading(tab, colSpan);

    const url = API.list + '?status=' + tab + '&year=' + year;

    fetch(url, {
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': CSRF
      }
    })
      .then(function (res) {
        return res.json().then(function (data) {
          if (!res.ok) {
            var msg = data.message || data.error || 'HTTP ' + res.status;
            throw new Error('[' + res.status + '] ' + msg);
          }
          return data;
        });
      })
      .then(function (data) {
        // Accept both { data: [...] } and a plain array.
        const rows = Array.isArray(data) ? data : (data.data ?? []);

        // Update badge count
        const b = badge(tab);
        if (b) b.textContent = rows.length;

        if (rows.length === 0) {
          const msgs = {
            pending: 'No pending requests for ' + year,
            approved: 'No approved requests for ' + year,
            disapproved: 'No disapproved requests for ' + year
          };
          setEmpty(tab, colSpan, msgs[tab]);
          return;
        }

        // Render rows
        const el = tbody(tab);
        if (!el) return;
        el.innerHTML = rows
          .map(function (row, i) {
            return tab === 'disapproved' ? buildRow7(row, i + 1) : buildRow6(row, i + 1);
          })
          .join('');

        state[tab].loaded = true;
      })
      .catch(function (err) {
        console.error('[LeaveRequests] loadTab(' + tab + ') failed:', err);
        // Show the real error so you can diagnose without opening devtools
        setError(tab, colSpan, err.message || 'Could not load data. Please refresh the page.');
      });
  }

  // ── Bootstrap tab switch — lazy load ─────────────────────────────────────────

  /**
   * Load a tab's data the first time it becomes visible,
   * or when the user switches to it after a year change.
   */
  function bindTabSwitch() {
    ['approved', 'disapproved'].forEach(function (tab) {
      const btn = document.getElementById('tab-' + tab);
      if (!btn) return;
      btn.addEventListener('shown.bs.tab', function () {
        if (!state[tab].loaded) {
          loadTab(tab, state[tab].year);
        }
      });
    });
  }

  // ── View modal ────────────────────────────────────────────────────────────────

  function openModal(id) {
    // Reset modal state
    document.getElementById('modal-employee-name').textContent = '';
    document.getElementById('modal-file-id').textContent = id;
    document.getElementById('request-timeline').innerHTML = '';
    document.getElementById('btn-track-container').innerHTML = '';
    document.getElementById('track-files-attachments-container').innerHTML = '';

    const iframe = document.querySelector('.frame-preview-file');
    if (iframe) iframe.src = '';

    // Open modal first — show skeleton
    const modalEl = document.getElementById('view-leave-request-modal');
    const modal = bootstrap.Modal.getOrCreate(modalEl);
    modal.show();

    // Fetch detail
    fetch(API.view + '/' + id, {
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': CSRF
      }
    })
      .then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(function (data) {
        const rec = data.data ?? data;

        // Populate employee name in modal title
        const nameEl = document.getElementById('modal-employee-name');
        if (nameEl) nameEl.textContent = rec.employee_name ?? '';

        // Build timeline
        const tl = document.getElementById('request-timeline');
        if (tl && Array.isArray(rec.timeline)) {
          tl.innerHTML = rec.timeline
            .map(function (step) {
              return (
                '<li class="ms-4">' +
                '<strong>' +
                escHtml(step.label ?? '') +
                '</strong>' +
                (step.date ? ' — <span class="text-muted">' + fmtDate(step.date) + '</span>' : '') +
                (step.note ? '<br><small class="text-muted">' + escHtml(step.note) + '</small>' : '') +
                '</li>'
              );
            })
            .join('');
        }

        // Populate attachments list
        const attList = document.getElementById('track-files-attachments-container');
        if (attList && Array.isArray(rec.attachments)) {
          attList.innerHTML =
            rec.attachments.length === 0
              ? '<li class="list-group-item text-muted">No attachments</li>'
              : rec.attachments
                  .map(function (att) {
                    return (
                      '<li class="list-group-item d-flex align-items-center justify-content-between">' +
                      '<span><i class="ri ri-file-line me-2 text-primary"></i>' +
                      escHtml(att.name ?? 'Attachment') +
                      '</span>' +
                      '<button class="btn btn-sm btn-outline-primary"' +
                      ' onclick="leaveRequests.openAttachment(\'' +
                      escHtml(att.url) +
                      '\')">' +
                      '<i class="ri ri-eye-line me-1"></i>View' +
                      '</button>' +
                      '</li>'
                    );
                  })
                  .join('');
        }

        // Set leave file iframe
        if (iframe && rec.file_url) iframe.src = rec.file_url;
      })
      .catch(function (err) {
        console.error('[LeaveRequests] openModal(' + id + ') failed:', err);
        const tl = document.getElementById('request-timeline');
        if (tl) {
          tl.innerHTML =
            '<li class="text-danger">' +
            '<i class="ri ri-error-warning-line me-1"></i>' +
            'Could not load request details.' +
            '</li>';
        }
      });
  }

  // ── Attachment modal ──────────────────────────────────────────────────────────

  function openAttachment(url) {
    const iframe = document.querySelector('.frame-preview-attachment');
    if (iframe) iframe.src = url;
    const modalEl = document.getElementById('view-attachment-modal');
    if (modalEl) bootstrap.Modal.getOrCreate(modalEl).show();
  }

  // ── Delete confirm ────────────────────────────────────────────────────────────

  function confirmDelete(id) {
    if (!confirm('Are you sure you want to delete this leave request? This cannot be undone.')) {
      return;
    }

    fetch(API.delete + '/' + id, {
      method: 'DELETE',
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': CSRF
      }
    })
      .then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(function () {
        // Reload the pending tab (deleted requests are always pending)
        loadTab('pending', state.pending.year);
      })
      .catch(function (err) {
        console.error('[LeaveRequests] confirmDelete(' + id + ') failed:', err);
        alert('Could not delete the request. Please try again.');
      });
  }

  // ── Init ──────────────────────────────────────────────────────────────────────

  document.addEventListener('DOMContentLoaded', function () {
    // Load pending tab immediately (it is the default visible tab)
    loadTab('pending', state.pending.year);

    // Lazy-load other tabs when first shown
    bindTabSwitch();
  });

  // ── Public API ────────────────────────────────────────────────────────────────
  window.leaveRequests = {
    loadTab: loadTab,
    openModal: openModal,
    openAttachment: openAttachment,
    confirmDelete: confirmDelete
  };
})();
