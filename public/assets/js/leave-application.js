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

  // ════════════════════════════════════════════════════════════════
  //  EMPLOYEE SEARCH
  // ════════════════════════════════════════════════════════════════
  const searchInput = document.getElementById('employeeSearchInput');
  const dropdown = document.getElementById('employeeDropdown');
  const clearBtn = document.getElementById('clearEmployeeBtn');

  let searchTimer = null;

  function populateEmployee(emp) {
    // Hidden fields
    document.getElementById('selectedEmployeeId').value = emp.id ?? '';
    document.getElementById('hiddenLastName').value = emp.last_name ?? '';
    document.getElementById('hiddenFirstName').value = emp.first_name ?? '';
    document.getElementById('hiddenMiddleName').value = emp.middle_name ?? '';
    document.getElementById('hiddenPositionId').value = emp.position_id ?? '';

    // Display fields
    document.getElementById('displayLastName').textContent = emp.last_name || '—';
    document.getElementById('displayFirstName').textContent = emp.first_name || '—';
    document.getElementById('displayMiddleName').textContent = emp.middle_name || '—';
    document.getElementById('displayPosition').textContent = emp.position_name || '—';
    document.getElementById('displayOffice').textContent = emp.office_name || '—';

    // Search input shows the selected employee
    if (searchInput) {
      searchInput.value = emp.employee_number + ' — ' + emp.first_name + ' ' + emp.last_name;
    }

    closeDropdown();
    if (clearBtn) clearBtn.style.display = 'inline-flex';

    // Fetch and render leave balances for this employee
    fetchLeaveBalances(emp.id);
  }

  function clearEmployee() {
    ['selectedEmployeeId', 'hiddenLastName', 'hiddenFirstName', 'hiddenMiddleName', 'hiddenPositionId'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.value = '';
    });

    ['displayLastName', 'displayFirstName', 'displayMiddleName'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.textContent = '—';
    });

    const pos = document.getElementById('displayPosition');
    if (pos) pos.textContent = '—';

    const off = document.getElementById('displayOffice');
    if (off) off.textContent = '—';

    if (searchInput) searchInput.value = '';
    if (clearBtn) clearBtn.style.display = 'none';

    // Hide and reset the leave balance panel
    hideBalancePanel();
    currentBalances = {};
  }

  function closeDropdown() {
    if (dropdown) dropdown.style.display = 'none';
    if (dropdown) dropdown.innerHTML = '';
  }

  // ════════════════════════════════════════════════════════════════
  //  LEAVE BALANCE PANEL
  //
  //  fetchLeaveBalances(employeeId)
  //    → GET /admin/api/employees/{id}/leave-balances
  //    → renders one card per tracked leave type (VL / SL / SIL)
  //
  //  Colour thresholds:
  //    ≥ 60 % remaining → success (green)
  //    20–59 %          → warning (amber)
  //    < 20 %           → danger  (red)
  // ════════════════════════════════════════════════════════════════
  // ── Leave balance store (populated when employee is selected) ────
  // Keyed by slug: { vacation: {...}, sick: {...}, sil: {...} }
  let currentBalances = {};

  function fetchLeaveBalances(employeeId) {
    const panel = document.getElementById('leaveBalancePanel');
    const spinner = document.getElementById('leaveBalanceSpinner');
    const cards = document.getElementById('leaveBalanceCards');
    const errBox = document.getElementById('leaveBalanceError');
    const errMsg = document.getElementById('leaveBalanceErrorMsg');
    const yearLabel = document.getElementById('leaveBalanceYear');

    if (!panel) return;

    // Show panel in loading state
    cards.innerHTML = '';
    errBox.style.setProperty('display', 'none', 'important');
    spinner.style.display = 'inline-flex';
    panel.style.display = 'block';

    fetch('/admin/api/employees/' + encodeURIComponent(employeeId) + '/leave-balances', {
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF }
    })
      .then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.json();
      })
      .then(function (data) {
        spinner.style.display = 'none';
        if (yearLabel) yearLabel.textContent = 'As of ' + data.year;

        // Store balances for real-time checking
        currentBalances = {};
        (data.balances ?? []).forEach(function (b) {
          currentBalances[b.slug] = b;
        });

        renderBalanceCards(data.balances ?? []);
        checkBalanceWarnings(); // run once after load
      })
      .catch(function (err) {
        spinner.style.display = 'none';
        errMsg.textContent = 'Could not load leave balances. Please try again.';
        errBox.style.removeProperty('display'); // override the !important none
        errBox.style.display = 'flex';
        console.error('Leave balance fetch error:', err);
      });
  }

  function renderBalanceCards(balances) {
    const cards = document.getElementById('leaveBalanceCards');
    if (!cards) return;

    if (!balances.length) {
      cards.innerHTML =
        '<div class="col-12"><p class="text-muted mb-0" style="font-size:.85rem;">' +
        '<i class="ri ri-information-line me-1"></i>No leave balance records found for this employee.' +
        '</p></div>';
      return;
    }

    // Icon map per leave type slug
    const icons = {
      vacation: 'ri-sun-line',
      sick: 'ri-heart-pulse-line',
      sil: 'ri-briefcase-line'
    };

    cards.innerHTML = balances
      .map(function (b) {
        const pct = b.total_days > 0 ? (b.remaining_days / b.total_days) * 100 : 0;
        const barPct = Math.max(0, Math.min(100, pct));
        const barColor = pct >= 60 ? 'bg-success' : pct >= 20 ? 'bg-warning' : 'bg-danger';
        const textColor = pct >= 60 ? 'text-success' : pct >= 20 ? 'text-warning' : 'text-danger';
        const icon = icons[b.slug] ?? 'ri-calendar-check-line';

        return (
          '<div class="col-12 col-sm-6 col-xl-4">' +
          '<div class="balance-card p-3 rounded-3 border h-100" data-slug="' +
          escHtml(b.slug) +
          '">' +
          '<div class="d-flex align-items-center justify-content-between mb-2">' +
          '<div class="d-flex align-items-center gap-2">' +
          '<span class="balance-card-icon"><i class="ri ' +
          escHtml(icon) +
          '"></i></span>' +
          '<span class="fw-semibold" style="font-size:.875rem;">' +
          escHtml(b.name) +
          '</span>' +
          '</div>' +
          '<span class="badge rounded-pill ' +
          (pct >= 60 ? 'bg-label-success' : pct >= 20 ? 'bg-label-warning' : 'bg-label-danger') +
          '" ' +
          'style="font-size:.75rem;">' +
          b.remaining_days +
          ' left' +
          '</span>' +
          '</div>' +
          // Progress bar
          '<div class="progress mb-2" style="height:6px;border-radius:3px;">' +
          '<div class="progress-bar ' +
          barColor +
          '" role="progressbar" ' +
          'style="width:' +
          barPct.toFixed(1) +
          '%" ' +
          'aria-valuenow="' +
          barPct.toFixed(1) +
          '" aria-valuemin="0" aria-valuemax="100">' +
          '</div>' +
          '</div>' +
          // Stats row
          '<div class="d-flex justify-content-between" style="font-size:.78rem;">' +
          '<span class="text-muted">Used: <strong>' +
          b.used_days +
          '</strong> / ' +
          b.total_days +
          ' days</span>' +
          '<span class="' +
          textColor +
          ' fw-semibold">' +
          Math.round(pct) +
          '%</span>' +
          '</div>' +
          // Policy note
          '<p class="text-muted mt-2 mb-0" style="font-size:.72rem;line-height:1.4;">' +
          '<i class="ri ri-information-line me-1"></i>' +
          escHtml(b.policy) +
          '</p>' +
          '</div>' +
          '</div>'
        );
      })
      .join('');
  }

  function hideBalancePanel() {
    const panel = document.getElementById('leaveBalancePanel');
    const cards = document.getElementById('leaveBalanceCards');
    const errBox = document.getElementById('leaveBalanceError');
    const spinner = document.getElementById('leaveBalanceSpinner');
    if (!panel) return;
    panel.style.display = 'none';
    if (cards) cards.innerHTML = '';
    if (errBox) errBox.style.setProperty('display', 'none', 'important');
    if (spinner) spinner.style.display = 'none';
  }

  // ════════════════════════════════════════════════════════════════
  //  BALANCE WARNINGS
  //
  //  Called whenever: leave type selection changes OR total days changes.
  //  For each checked leave type, compare requested total_days against
  //  the employee's remaining balance and:
  //    • Highlight the matching balance card (active border + glow)
  //    • Show an inline warning if requested > remaining
  //    • Dim cards for leave types that are NOT selected
  // ════════════════════════════════════════════════════════════════
  function checkBalanceWarnings() {
    // Which leave type slugs are currently checked?
    const selectedSlugs = [...document.querySelectorAll('input[name="leave_type[]"]:checked')].map(function (inp) {
      return inp.value;
    });

    const totalRequested = parseFloat(document.getElementById('hiddenTotalDays')?.value ?? '0') || 0;

    // Remove all existing balance warnings
    document.querySelectorAll('.balance-warning').forEach(function (el) {
      el.remove();
    });

    // Reset all balance card styles
    document.querySelectorAll('.balance-card').forEach(function (card) {
      card.style.borderColor = '';
      card.style.boxShadow = '';
      card.style.opacity = '';
    });

    if (!selectedSlugs.length || !Object.keys(currentBalances).length) return;

    const cards = document.getElementById('leaveBalanceCards');
    if (!cards) return;

    // Dim all cards first, then highlight selected ones
    document.querySelectorAll('.balance-card').forEach(function (card) {
      const slug = card.dataset.slug;
      const isSelected = selectedSlugs.includes(slug);
      card.style.opacity = isSelected ? '1' : '0.45';
    });

    // Check each selected leave type
    selectedSlugs.forEach(function (slug) {
      const balance = currentBalances[slug];
      if (!balance) return;

      const cardEl = cards.querySelector('.balance-card[data-slug="' + slug + '"]');
      if (!cardEl) return;

      const remaining = balance.remaining_days ?? 0;
      const isExceeded = totalRequested > remaining;

      if (isExceeded) {
        // Red highlight — insufficient balance
        cardEl.style.borderColor = '#ea5455';
        cardEl.style.boxShadow = '0 0 0 3px rgba(234,84,85,.18)';

        // Inject warning below the policy note inside the card
        if (!cardEl.querySelector('.balance-warning')) {
          const warn = document.createElement('div');
          warn.className = 'balance-warning d-flex align-items-start gap-1 mt-2';
          warn.style.cssText = 'font-size:.72rem;color:#ea5455;line-height:1.4;';
          warn.innerHTML =
            '<i class="ri ri-error-warning-line flex-shrink-0 mt-1" style="font-size:.85rem;"></i>' +
            '<span>Requested <strong>' +
            totalRequested +
            '</strong> day(s) exceeds your remaining balance of ' +
            '<strong>' +
            remaining +
            '</strong> day(s).</span>';
          cardEl.appendChild(warn);
        }
      } else {
        // Green/normal highlight — sufficient balance
        cardEl.style.borderColor = '#28c76f';
        cardEl.style.boxShadow = '0 0 0 3px rgba(40,199,111,.15)';
      }
    });
  }

  function renderDropdown(results) {
    if (!dropdown) return;
    if (!results.length) {
      dropdown.innerHTML =
        '<li class="emp-dd-empty"><i class="ri ri-user-unfollow-line me-2"></i>No employees found.</li>';
      dropdown.style.display = 'block';
      return;
    }
    dropdown.innerHTML = results
      .map(
        emp =>
          '<li class="emp-dd-item" data-id="' +
          emp.id +
          '">' +
          '<span class="emp-dd-num">' +
          escHtml(emp.employee_number) +
          '</span>' +
          '<span class="emp-dd-name">' +
          escHtml(emp.first_name + ' ' + (emp.middle_name ? emp.middle_name + ' ' : '') + emp.last_name) +
          '</span>' +
          '<span class="emp-dd-pos">' +
          escHtml(emp.position_name ?? '') +
          '</span>' +
          '</li>'
      )
      .join('');
    dropdown.style.display = 'block';

    dropdown.querySelectorAll('.emp-dd-item').forEach(function (li) {
      li.addEventListener('mousedown', function (e) {
        e.preventDefault(); // prevent blur before click
        const id = li.dataset.id;
        const emp = results.find(r => String(r.id) === String(id));
        if (emp) populateEmployee(emp);
      });
    });
  }

  function doSearch(q) {
    if (q.length < 2) {
      closeDropdown();
      return;
    }
    fetch('/admin/api/employees/search?q=' + encodeURIComponent(q), {
      headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF }
    })
      .then(res => res.json())
      .then(data => renderDropdown(Array.isArray(data) ? data : (data.data ?? [])))
      .catch(() => closeDropdown());
  }

  if (searchInput) {
    searchInput.addEventListener('input', function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => doSearch(searchInput.value.trim()), 280);
    });
    searchInput.addEventListener('blur', function () {
      // small delay so mousedown on dropdown fires first
      setTimeout(closeDropdown, 180);
    });
    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        closeDropdown();
        searchInput.blur();
      }
    });
  }

  if (clearBtn) {
    clearBtn.addEventListener('click', clearEmployee);
  }

  // Close dropdown on outside click
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.employee-search-wrap')) closeDropdown();
  });

  // ════════════════════════════════════════════════════════════════
  //  LEAVE CARD — MULTI-SELECT (checkboxes) + COMMUTATION (radio)
  //
  //  .leave-input[type="checkbox"]  → leave type cards  (multi)
  //  .leave-input[type="radio"]     → commutation cards (single)
  // ════════════════════════════════════════════════════════════════
  document.querySelectorAll('.leave-card').forEach(function (card) {
    card.addEventListener('click', function () {
      const input = card.querySelector('.leave-input');
      if (!input) return;

      if (input.type === 'checkbox') {
        // Toggle this card
        input.checked = !input.checked;
        card.classList.toggle('lc-selected', input.checked);
        // Re-check balance warnings whenever selection changes
        checkBalanceWarnings();
      } else {
        // Radio — single select within same name group
        input.checked = true;
        const groupName = input.name;
        document.querySelectorAll('.leave-card').forEach(function (c) {
          const inp = c.querySelector('.leave-input');
          if (inp && inp.name === groupName) {
            c.classList.toggle('lc-selected', inp.checked);
          }
        });
      }
    });
  });

  // ── Working-day calculator ────────────────────────────────────
  function countWorkingDays(from, to) {
    let count = 0;
    const cur = new Date(from + 'T00:00:00');
    const end = new Date(to + 'T00:00:00');
    while (cur <= end) {
      if (cur.getDay() !== 0) count++;
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
    // Keep the hidden field in sync so the server always receives the correct value
    const hidden = document.getElementById('hiddenTotalDays');
    if (hidden) hidden.value = total;
    // Re-evaluate balance warnings whenever days change
    checkBalanceWarnings();
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

      // Validate employee selected
      if (!document.getElementById('selectedEmployeeId')?.value) {
        showToast('Please search and select an employee before submitting.', 'danger');
        document.getElementById('employeeSearchInput')?.focus();
        return;
      }

      // Validate at least one leave type checked
      if (!document.querySelector('input[name="leave_type[]"]:checked')) {
        showToast('Please select at least one leave type before submitting.', 'danger');
        return;
      }

      // Validate leave duration is set
      const totalDays = parseFloat(document.getElementById('hiddenTotalDays')?.value ?? '0');
      if (!totalDays || totalDays <= 0) {
        showToast('Please enter leave dates before submitting.', 'danger');
        document.getElementById('wholeDayFrom')?.focus();
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
    clearEmployee(); // also calls hideBalancePanel()

    // Clear all leave type card highlights (checkboxes)
    document.querySelectorAll('.leave-card').forEach(c => {
      c.classList.remove('lc-selected');
      const inp = c.querySelector('.leave-input');
      if (inp) inp.checked = false;
    });

    // Re-apply commutation default (Not Requested is pre-checked in HTML)
    document.querySelectorAll('.commut-card').forEach(function (c) {
      c.classList.toggle('lc-selected', c.querySelector('.leave-input')?.checked ?? false);
    });

    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

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
    currentBalances = {};
    document.querySelectorAll('.balance-warning').forEach(function (el) {
      el.remove();
    });
    const td = document.getElementById('totalDaysDisplay');
    const wr = document.getElementById('wholeDayResult');
    const we = document.getElementById('wholeDayError');
    if (td) td.textContent = '0';
    if (wr) wr.textContent = '—';
    if (we) we.style.display = 'none';
  };

  // ── HTML escape helper ────────────────────────────────────────
  function escHtml(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }
})();
