// assets/js/leave-application.js

(function () {
  // ── CSRF token ────────────────────────────────────────────────
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

  // ── Auto-set filing date on load ─────────────────────────────
  (function setFilingDate() {
    const now = new Date();
    const opts = { year: 'numeric', month: 'long', day: 'numeric' };
    const display = document.getElementById('filingDateDisplay');
    const hidden = document.getElementById('filingDateHidden');
    if (display) display.value = now.toLocaleDateString('en-PH', opts);
    if (hidden) hidden.value = now.toISOString().split('T')[0];
  })();

  // ── Leave card single-selection ───────────────────────────────
  //
  //  Two independent radio groups on this page:
  //    name="leave_type"  — the 6 leave type cards (col-lg-8)
  //    name="commutation" — the 2 commutation cards (col-lg-4)
  //
  //  Both share .leave-card / .leave-input markup.
  //  Clicking a card checks its radio and syncs lc-selected
  //  only within that same name group so the two sides never
  //  interfere with each other.
  // ─────────────────────────────────────────────────────────────
  document.querySelectorAll('.leave-card').forEach(function (card) {
    card.addEventListener('click', function () {
      const input = card.querySelector('.leave-input');
      if (!input) return;

      input.checked = true;

      const groupName = input.name;
      document.querySelectorAll('.leave-card').forEach(function (c) {
        const inp = c.querySelector('.leave-input');
        if (inp && inp.name === groupName) {
          c.classList.toggle('lc-selected', inp.checked);
        }
      });
    });
  });

  // ── Working-day calculator ────────────────────────────────────
  function countWorkingDays(from, to) {
    let count = 0;
    const cur = new Date(from + 'T00:00:00');
    const end = new Date(to + 'T00:00:00');
    while (cur <= end) {
      if (cur.getDay() !== 0) count++; // exclude Sundays
      cur.setDate(cur.getDate() + 1);
    }
    return count;
  }

  let wholeDays = 0;
  let halfDayDays = 0;

  function refreshTotal() {
    const total = wholeDays + halfDayDays;
    const el = document.getElementById('totalDaysDisplay');
    if (el) el.textContent = total % 1 === 0 ? String(total) : total.toFixed(1);
  }

  window.computeWholeDays = function () {
    const from = document.getElementById('wholeDayFrom').value;
    const to = document.getElementById('wholeDayTo').value;
    const resEl = document.getElementById('wholeDayResult');
    const errEl = document.getElementById('wholeDayError');

    if (from && to) {
      if (to < from) {
        if (resEl) resEl.textContent = '—';
        if (errEl) errEl.style.display = 'block';
        wholeDays = 0;
      } else {
        if (errEl) errEl.style.display = 'none';
        wholeDays = countWorkingDays(from, to);
        if (resEl) resEl.textContent = wholeDays + ' working day' + (wholeDays !== 1 ? 's' : '');
      }
    } else {
      if (resEl) resEl.textContent = '—';
      if (errEl) errEl.style.display = 'none';
      wholeDays = 0;
    }
    refreshTotal();
  };

  // ── Half-day rows ─────────────────────────────────────────────
  let hdIdx = 0;

  window.addHalfDay = function () {
    const container = document.getElementById('halfDayContainer');
    const empty = document.getElementById('halfDayEmpty');
    if (empty) empty.style.display = 'none';

    const i = hdIdx++;
    const row = document.createElement('div');
    row.className = 'half-day-row';
    row.id = 'hd-' + i;
    row.innerHTML =
      '<div>' +
      '<label class="form-label">Date</label>' +
      '<input type="date" name="half_day[' +
      i +
      '][date]"' +
      ' class="form-control hd-date" onchange="recomputeHalfDays()">' +
      '</div>' +
      '<div>' +
      '<label class="form-label">Session</label>' +
      '<select name="half_day[' +
      i +
      '][session]" class="form-select">' +
      '<option value="AM">Morning (AM)</option>' +
      '<option value="PM">Afternoon (PM)</option>' +
      '</select>' +
      '</div>' +
      '<div class="d-flex align-items-end pb-1">' +
      '<button type="button" class="btn btn-outline-danger btn-sm"' +
      ' onclick="removeHalfDay(\'hd-' +
      i +
      '\')" title="Remove">' +
      '<i class="ri ri-delete-bin-line"></i>' +
      '</button>' +
      '</div>';
    container.appendChild(row);
    recomputeHalfDays();
  };

  window.removeHalfDay = function (rowId) {
    document.getElementById(rowId)?.remove();
    if (!document.querySelector('.half-day-row')) {
      const empty = document.getElementById('halfDayEmpty');
      if (empty) empty.style.display = 'flex';
    }
    recomputeHalfDays();
  };

  window.recomputeHalfDays = function () {
    halfDayDays = [...document.querySelectorAll('.hd-date')].filter(d => d.value !== '').length * 0.5;
    refreshTotal();
  };

  // ── Salary formatter ──────────────────────────────────────────
  window.formatSalary = function (el) {
    let val = el.value.replace(/[^0-9.]/g, '');
    const pts = val.split('.');
    pts[0] = pts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    if (pts.length > 2) pts.splice(2);
    if (pts[1] !== undefined) pts[1] = pts[1].slice(0, 2);
    el.value = pts.join('.');
  };

  // ── Toast helper ──────────────────────────────────────────────
  function showToast(message, type) {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const id = 'toast-' + Date.now();
    const icon = type === 'success' ? 'ri-check-circle-line' : 'ri-error-warning-line';
    container.insertAdjacentHTML(
      'beforeend',
      '<div id="' +
        id +
        '" class="toast align-items-center text-white border-0 bg-' +
        type +
        '"' +
        ' role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">' +
        '<div class="d-flex">' +
        '<div class="toast-body d-flex align-items-center gap-2">' +
        '<i class="ri ' +
        icon +
        ' fs-5"></i>' +
        message +
        '</div>' +
        '<button type="button" class="btn-close btn-close-white me-2 m-auto"' +
        ' data-bs-dismiss="toast" aria-label="Close"></button>' +
        '</div>' +
        '</div>'
    );
    const toastEl = document.getElementById(id);
    new bootstrap.Toast(toastEl).show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
  }

  // ── Submit button spinner ─────────────────────────────────────
  function setSubmitting(btn, on) {
    if (on) {
      btn.disabled = true;
      btn.dataset.orig = btn.innerHTML;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Submitting…';
    } else {
      btn.disabled = false;
      btn.innerHTML = btn.dataset.orig;
    }
  }

  // ── AJAX form submission ──────────────────────────────────────
  const form = document.getElementById('leaveAppForm');
  const submitBtn = document.getElementById('submitBtn');

  if (form && submitBtn) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();

      if (!document.querySelector('input[name="leave_type"]:checked')) {
        showToast('Please select a leave type before submitting.', 'danger');
        return;
      }

      setSubmitting(submitBtn, true);

      fetch(form.getAttribute('action'), {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
        body: new FormData(form)
      })
        .then(res => res.json().then(data => ({ ok: res.ok, status: res.status, data })))
        .then(function ({ ok, status, data }) {
          setSubmitting(submitBtn, false);

          if (ok) {
            showToast(data.message ?? 'Leave application submitted successfully.', 'success');
            resetLeaveForm();
          } else if (status === 422) {
            const first = Object.values(data.errors ?? {})[0];
            showToast(Array.isArray(first) ? first[0] : (data.message ?? 'Validation failed.'), 'danger');
            highlightErrors(data.errors ?? {});
          } else {
            showToast(data.message ?? 'Something went wrong. Please try again.', 'danger');
          }
        })
        .catch(function (err) {
          setSubmitting(submitBtn, false);
          showToast('Network error. Please check your connection and try again.', 'danger');
          console.error('Leave form AJAX error:', err);
        });
    });
  }

  // ── Field error highlights ────────────────────────────────────
  function highlightErrors(errors) {
    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

    Object.entries(errors).forEach(function ([field, messages]) {
      // Convert dot-notation "half_day.0.date" → "half_day[0][date]"
      const name = field
        .replace(/\.(\d+)\./g, '[$1][')
        .replace(/\.(\d+)$/, '[$1]')
        .replace(/\./g, '][');
      const input = document.querySelector('[name="' + name + '"]') ?? document.querySelector('[name="' + field + '"]');
      if (!input) return;
      input.classList.add('is-invalid');
      const fb = document.createElement('div');
      fb.className = 'invalid-feedback';
      fb.textContent = messages[0];
      input.insertAdjacentElement('afterend', fb);
    });
  }

  // ── Form reset ────────────────────────────────────────────────
  window.resetLeaveForm = function () {
    form.reset();

    // Clear all card highlights
    document.querySelectorAll('.leave-card').forEach(c => c.classList.remove('lc-selected'));

    // Re-apply commutation default (Not Requested is pre-checked in HTML)
    document.querySelectorAll('.commut-card').forEach(function (c) {
      c.classList.toggle('lc-selected', c.querySelector('.leave-input')?.checked ?? false);
    });

    // Clear validation states
    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

    // Reset half-day rows
    const container = document.getElementById('halfDayContainer');
    if (container) {
      container.innerHTML =
        '<div id="halfDayEmpty" class="text-muted d-flex align-items-center gap-2 py-2"' +
        ' style="font-size:.83rem;">' +
        '<i class="ri ri-calendar-line opacity-50 fs-5"></i>' +
        'No half-day entries added yet. Click <strong class="mx-1">Add Half Day</strong> to include one.' +
        '</div>';
    }

    wholeDays = halfDayDays = hdIdx = 0;

    const td = document.getElementById('totalDaysDisplay');
    const wr = document.getElementById('wholeDayResult');
    const we = document.getElementById('wholeDayError');
    if (td) td.textContent = '0';
    if (wr) wr.textContent = '—';
    if (we) we.style.display = 'none';
  };
})();
