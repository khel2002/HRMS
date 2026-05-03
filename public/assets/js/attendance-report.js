/**
 * attendance-report.js
 * ─────────────────────────────────────────────────────────────────────────────
 * Daily attendance report — client-side logic.
 *
 * Sections:  Summary cards | Late Employees | On Leave | Absent | Pending
 *
 * Features:
 *   • Prev/Next/Today day navigation — weekends auto-skipped.
 *   • Client-side weekend/future guard — no AJAX for invalid dates.
 *   • Time-based absent/pending toggle: before 12:00 PM → Pending shown,
 *     Absent hidden. After 12:00 PM → Absent shown, Pending hidden.
 *   • Notice panel for weekend/future/error states.
 *   • Shimmer skeleton loaders during fetch.
 *   • In-flight XHR aborted on rapid navigation.
 *   • Inline Retry button on network/server failure.
 *   • Stat-card numbers animate (ease-out cubic) on data update.
 *   • Live employee search + section filter (debounced 200 ms).
 *   • All dynamic HTML sanitised via escHtml() (XSS-safe).
 *
 * Requires: jQuery ≥ 3.x  |  window.AttConfig injected by Blade
 * ─────────────────────────────────────────────────────────────────────────────
 */
(function ($) {
  'use strict';

  /* ═══════════════════════════════════════════════════════════════════════════
     CONFIG — injected by Blade
  ═══════════════════════════════════════════════════════════════════════════ */
  const CFG = window.AttConfig ?? {};
  const ROUTE_DAILY = CFG.routes?.daily ?? '/admin/attendance-report/daily';
  const ROUTE_PDF = CFG.routes?.pdf ?? '/admin/attendance-report/pdf';
  const TODAY_STR = CFG.today ?? toISO(new Date());
  const CUTOFF_TIME = CFG.cutoffTime ?? '12:00:00'; // HH:MM:SS

  /* ═══════════════════════════════════════════════════════════════════════════
     STATE
  ═══════════════════════════════════════════════════════════════════════════ */
  let currentDate = parseISO(CFG.initialDate ?? TODAY_STR);
  let activeXHR = null;

  /* ═══════════════════════════════════════════════════════════════════════════
     INIT
  ═══════════════════════════════════════════════════════════════════════════ */
  $(function () {
    bindDayNav();
    bindFilters();
    bindExportButtons();
    refreshDateLabel();
    // If today: poll every 60 s so absent list activates at cutoff without refresh.
    if (toISO(currentDate) === TODAY_STR) {
      setInterval(pollCutoff, 60_000);
    }
    // First paint is server-rendered — no AJAX on initial load.
  });

  /* ═══════════════════════════════════════════════════════════════════════════
     CUTOFF POLL (today only)
     Checks whether the absent section should now be shown without a full reload.
  ═══════════════════════════════════════════════════════════════════════════ */
  function pollCutoff() {
    if (toISO(currentDate) !== TODAY_STR) return;
    const nowTime = nowHMS();
    const alreadyShowing = $('#section-absent').is(':visible');

    if (!alreadyShowing && nowTime >= CUTOFF_TIME) {
      // Cutoff just passed — re-fetch to get the fresh absent list.
      fetchDailyData();
    }
  }

  /* ═══════════════════════════════════════════════════════════════════════════
     DAY NAVIGATION
  ═══════════════════════════════════════════════════════════════════════════ */
  /* ═══════════════════════════════════════════════════════════════════════════
     EXPORT BUTTONS
  ═══════════════════════════════════════════════════════════════════════════ */
  function bindExportButtons() {
    // PDF — opens in new tab for the currently viewed date
    $('#btnExportPdf').on('click', function () {
      const dateStr = toISO(currentDate);
      const url = ROUTE_PDF + '?date=' + dateStr;
      window.open(url, '_blank');
    });

    // Excel — placeholder; implement server-side export as needed
    $('#btnExportExcel').on('click', function () {
      alert('Excel export coming soon.');
    });
  }

  function bindDayNav() {
    $('#btnPrevDay').on('click', function () {
      currentDate = prevWorkday(currentDate);
      onDateChanged();
    });
    $('#btnNextDay').on('click', function () {
      currentDate = nextWorkday(currentDate);
      onDateChanged();
    });
    $('#btnToday').on('click', function () {
      currentDate = parseISO(TODAY_STR);
      onDateChanged();
    });
  }

  function onDateChanged() {
    refreshDateLabel();
    fetchDailyData();
  }

  function refreshDateLabel() {
    const d = currentDate;
    // Subtitle below page title
    $('#att-date-subtitle').text(fmtLong(d));
    // Date navigator label span
    $('#datePickerInput').text(fmtMMDDYYYY(d));
  }

  /* ═══════════════════════════════════════════════════════════════════════════
     AJAX
  ═══════════════════════════════════════════════════════════════════════════ */
  function fetchDailyData() {
    if (activeXHR) {
      activeXHR.abort();
      activeXHR = null;
    }

    const dateStr = toISO(currentDate);

    // ── Client-side guards ─────────────────────────────────────────────────
    if (isWeekend(currentDate)) {
      showNotice('weekend', 'Attendance is not recorded on weekends (Saturday & Sunday).');
      return;
    }
    if (dateStr > TODAY_STR) {
      showNotice('future', 'No available data yet for this date.');
      return;
    }

    showSkeletons();

    activeXHR = $.ajax({
      url: ROUTE_DAILY,
      method: 'GET',
      data: { date: dateStr },
      dataType: 'json'
    })
      .done(function (res) {
        if (!res.success) {
          if (res.reason === 'future' || res.reason === 'weekend') {
            showNotice(res.reason, res.message);
          } else {
            showErrorBanner(res.message ?? 'An unexpected error occurred.');
          }
          return;
        }

        hideNotice();
        renderSummary(res.summary);
        renderLate(res.late ?? []);
        renderOnLeave(res.on_leave ?? []);
        renderAbsent(res.absent ?? [], res.cutoff_passed);
        renderPending(res.pending ?? [], res.cutoff_passed);
        applyFilters();
      })
      .fail(function (xhr) {
        if (xhr.statusText === 'abort') return;
        if (xhr.status === 422 && xhr.responseJSON?.reason) {
          showNotice(xhr.responseJSON.reason, xhr.responseJSON.message);
        } else {
          hideNotice();
          showErrorBanner(xhr.responseJSON?.message ?? 'Could not load attendance data. Please try again.');
        }
      })
      .always(function () {
        activeXHR = null;
      });
  }

  /* ═══════════════════════════════════════════════════════════════════════════
     RENDERERS
  ═══════════════════════════════════════════════════════════════════════════ */

  // ── Summary stat cards ─────────────────────────────────────────────────────
  function renderSummary(summary) {
    if (!summary) return;
    Object.entries(summary).forEach(([key, value]) => {
      animateNumber(`[data-stat="${key}"]`, value);
    });
  }

  // ── Late Employees ─────────────────────────────────────────────────────────
  function renderLate(list) {
    $('#count-late').text(list.length);
    const $tbody = $('#late-list').empty();

    if (!list.length) {
      $tbody.html(emptyRow(4, 'No late arrivals on this day.'));
      return;
    }

    list.forEach(emp => {
      const severityClass = emp.late_minutes >= 60 ? 'is-severe' : emp.late_minutes >= 30 ? 'is-moderate' : '';

      $tbody.append(`
        <tr class="daily-searchable" data-name="${emp.name.toLowerCase()}" data-group="late">
          <td class="ps-4 py-3">
            <div class="d-flex align-items-center gap-3">
              <div class="att-avatar av-c${emp.av}">${makeInitials(emp.name)}</div>
              <span class="fw-medium">${escHtml(emp.name)}</span>
            </div>
          </td>
          <td class="py-3">
            <div class="fw-medium">${escHtml(emp.pos)}</div>
            <div class="text-muted" style="font-size:.78rem;">${escHtml(emp.dept)}</div>
          </td>
          <td class="py-3 fw-medium">${escHtml(emp.time_in)}</td>
          <td class="py-3">
            <span class="att-late-badge ${severityClass}">
              <i class="ri-time-line"></i> ${escHtml(emp.late_duration)}
            </span>
          </td>
        </tr>
      `);
    });
  }

  // ── On Leave (grouped by leave type, matching screenshot) ─────────────────
  const LEAVE_COLORS = {
    'service incentive leave': { header: '#eef2ff', accent: '#7367f0', bg: '#e8eeff' },
    'vacation leave': { header: '#e8f5e9', accent: '#28c76f', bg: '#dff5e3' },
    'sick leave': { header: '#fff1f1', accent: '#ea5455', bg: '#fce4e4' }
  };

  function leaveColorFor(typeName) {
    const key = (typeName ?? '').toLowerCase();
    for (const [k, v] of Object.entries(LEAVE_COLORS)) {
      if (key.includes(k.split(' ')[0])) return v; // match first word
    }
    return { header: '#f5f5f5', accent: '#888', bg: '#f0f0f0' };
  }

  function renderOnLeave(list) {
    $('#count-leave').text(list.length);
    const $container = $('#leave-groups-container').empty();

    if (!list.length) {
      $container.html(
        `<div class="card shadow-none border"><div class="card-body">${emptyState('No employees on leave on this day.')}</div></div>`
      );
      return;
    }

    // Group by leave_type
    const groups = {};
    list.forEach(emp => {
      if (!groups[emp.leave_type]) groups[emp.leave_type] = [];
      groups[emp.leave_type].push(emp);
    });

    Object.entries(groups).forEach(([leaveType, employees]) => {
      const c = leaveColorFor(leaveType);
      const count = employees.length;
      const label = count === 1 ? '1 employee' : `${count} employees`;

      let rows = employees
        .map(
          emp => `
        <tr class="daily-searchable" data-name="${emp.name.toLowerCase()}" data-group="leave">
          <td class="ps-4 py-3">
            <div class="d-flex align-items-center gap-3">
              <div class="att-avatar" style="background:${c.bg};color:${c.accent};">${makeInitials(emp.name)}</div>
              <span class="fw-medium">${escHtml(emp.name)}</span>
            </div>
          </td>
        </tr>
      `
        )
        .join('');

      $container.append(`
        <div class="mb-4 daily-searchable-group" data-group="leave">
          <div class="d-flex align-items-center justify-content-between mb-2 px-1">
            <span class="fw-semibold" style="font-size:.9rem;">${escHtml(leaveType)}</span>
            <span class="text-muted" style="font-size:.8rem;">${label}</span>
          </div>
          <div class="card shadow-none border">
            <div class="table-responsive">
              <table class="table mb-0" style="font-size:.85rem;">
                <thead>
                  <tr style="background:${c.header};">
                    <th class="att-th ps-4" style="color:${c.accent};">EMPLOYEE NAME</th>
                  </tr>
                </thead>
                <tbody>${rows}</tbody>
              </table>
            </div>
          </div>
        </div>
      `);
    });
  }

  // ── Absent Employees ───────────────────────────────────────────────────────
  function renderAbsent(list, cutoffPassed) {
    $('#count-absent').text(list.length);

    // Show/hide the whole section based on cutoff
    if (cutoffPassed) {
      $('#section-absent').show();
      $('#section-pending').hide();
      $('#stat-pending-card').hide();
    } else {
      $('#section-absent').hide();
    }

    const $tbody = $('#absent-list').empty();

    if (!list.length) {
      $tbody.html(emptyRow(3, 'No absences recorded.'));
      return;
    }

    list.forEach(emp => {
      $tbody.append(`
        <tr class="daily-searchable" data-name="${emp.name.toLowerCase()}" data-group="absent">
          <td class="ps-4 py-3">
            <div class="d-flex align-items-center gap-3">
              <div class="att-avatar av-absent">${makeInitials(emp.name)}</div>
              <span class="fw-medium">${escHtml(emp.name)}</span>
            </div>
          </td>
          <td class="py-3">
            <div class="fw-medium">${escHtml(emp.pos)}</div>
            <div class="text-muted" style="font-size:.78rem;">${escHtml(emp.dept)}</div>
          </td>
          <td class="py-3">
            <span class="badge bg-label-danger rounded-pill">Absent</span>
          </td>
        </tr>
      `);
    });
  }

  // ── Pending Employees (before cutoff, no time-in) ──────────────────────────
  function renderPending(list, cutoffPassed) {
    $('#count-pending').text(list.length);

    if (!cutoffPassed) {
      $('#section-pending').show();
      $('#stat-pending-card').show();
    } else {
      $('#section-pending').hide();
      $('#stat-pending-card').hide();
    }

    const $tbody = $('#pending-list').empty();

    if (!list.length) {
      $tbody.html(emptyRow(3, 'All employees have clocked in.'));
      return;
    }

    list.forEach(emp => {
      $tbody.append(`
        <tr class="daily-searchable" data-name="${emp.name.toLowerCase()}" data-group="pending">
          <td class="ps-4 py-3">
            <div class="d-flex align-items-center gap-3">
              <div class="att-avatar av-pending">${makeInitials(emp.name)}</div>
              <span class="fw-medium">${escHtml(emp.name)}</span>
            </div>
          </td>
          <td class="py-3">
            <div class="fw-medium">${escHtml(emp.pos)}</div>
            <div class="text-muted" style="font-size:.78rem;">${escHtml(emp.dept)}</div>
          </td>
          <td class="py-3">
            <span class="badge bg-label-secondary rounded-pill">Not yet in</span>
          </td>
        </tr>
      `);
    });
  }

  /* ═══════════════════════════════════════════════════════════════════════════
     SKELETON LOADERS
  ═══════════════════════════════════════════════════════════════════════════ */
  function shimmer() {
    return `<div class="att-shimmer-row"></div>`;
  }

  function showSkeletons() {
    hideNotice();
    const skimRows = n =>
      `<tr><td colspan="4" style="padding:.5rem 1.5rem;">${Array(n).fill(shimmer()).join('')}</td></tr>`;
    $('#late-list').html(skimRows(4));
    $('#absent-list').html(skimRows(3));
    $('#pending-list').html(skimRows(3));
    $('#leave-groups-container').html(`<div style="padding:.5rem 0;">${Array(3).fill(shimmer()).join('')}</div>`);
    $('#count-late, #count-leave, #count-absent, #count-pending').text('…');
  }

  /* ═══════════════════════════════════════════════════════════════════════════
     NOTICE PANEL (weekend / future / error)
  ═══════════════════════════════════════════════════════════════════════════ */
  const NOTICE_ICONS = {
    weekend: 'ri-calendar-close-line',
    future: 'ri-hourglass-line',
    server_error: 'ri-error-warning-line'
  };

  function showNotice(reason, message) {
    const icon = NOTICE_ICONS[reason] ?? 'ri-information-line';
    $('#att-notice-body').html(`
      <i class="ri ${icon}" style="font-size:3rem;color:#a8aaae;"></i>
      <p class="text-muted mt-3 mb-0" style="font-size:.95rem;max-width:360px;margin:auto;">
        ${escHtml(message)}
      </p>
    `);
    $('#att-notice').show();
    $('#att-data-panels, #att-summary-section').hide();
    $('[data-stat]').text('—');
  }

  function hideNotice() {
    $('#att-notice').hide();
    $('#att-data-panels, #att-summary-section').show();
  }

  function showErrorBanner(message) {
    const html = `
      <div class="att-empty-state py-4 text-danger">
        <i class="ri-error-warning-line" style="font-size:2.5rem;"></i>
        <p class="mt-2 mb-3">${escHtml(message)}</p>
        <button class="btn btn-sm btn-outline-danger" id="btnRetry">
          <i class="ri-refresh-line me-1"></i>Retry
        </button>
      </div>
    `;
    $('#late-list').html(`<tr><td colspan="4">${html}</td></tr>`);
    $('#absent-list').html(`<tr><td colspan="3">${html}</td></tr>`);
    $('#pending-list').html(`<tr><td colspan="3">${html}</td></tr>`);
    $('#leave-groups-container').html(html);
    $(document).one('click', '#btnRetry', fetchDailyData);
  }

  /* ═══════════════════════════════════════════════════════════════════════════
     STAT CARD ANIMATION
  ═══════════════════════════════════════════════════════════════════════════ */
  function animateNumber(selector, target) {
    const $el = $(selector);
    if (!$el.length) return;
    const start = parseInt($el.text(), 10);
    if (isNaN(start) || start === target) {
      $el.text(target);
      return;
    }
    const STEPS = 20,
      MS = 350 / STEPS;
    let step = 0;
    const t = setInterval(function () {
      step++;
      const eased = 1 - Math.pow(1 - step / STEPS, 3);
      $el.text(Math.round(start + (target - start) * eased));
      if (step >= STEPS) {
        clearInterval(t);
        $el.text(target);
      }
    }, MS);
  }

  /* ═══════════════════════════════════════════════════════════════════════════
     SEARCH + FILTER
  ═══════════════════════════════════════════════════════════════════════════ */
  function bindFilters() {
    $('#dailySearch').on('input', debounce(applyFilters, 200));
    $('#dailyFilter').on('change', applyFilters);
  }

  function applyFilters() {
    const q = ($('#dailySearch').val() ?? '').trim().toLowerCase();
    const filter = $('#dailyFilter').val() ?? 'all';

    // Row-level filtering
    $('.daily-searchable').each(function () {
      const $el = $(this);
      const name = $el.data('name') ?? '';
      const group = $el.data('group') ?? '';
      const matchQ = !q || name.includes(q);
      const matchF = filter === 'all' || filter === group;
      $el.toggle(matchQ && matchF);
    });

    // Section-level visibility
    const sections = {
      late: '#section-late',
      leave: '#section-leave',
      absent: '#section-absent',
      pending: '#section-pending'
    };
    Object.entries(sections).forEach(([key, sel]) => {
      if (filter === 'all') {
        // Respect the cutoff logic for absent/pending
        if (key === 'absent') {
          if ($('#count-absent').text() !== '0' || filter === 'all') $(sel).show();
        } else if (key === 'pending') {
          /* controlled by cutoff */
        } else $(sel).show();
      } else {
        $(sel).toggle(filter === key);
      }
    });
  }

  /* ═══════════════════════════════════════════════════════════════════════════
     DATE HELPERS
  ═══════════════════════════════════════════════════════════════════════════ */
  const MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  const MONTHS_LONG = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December'
  ];
  const DAYS_LONG = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

  function parseISO(str) {
    const [y, m, d] = str.split('-').map(Number);
    const dt = new Date(y, m - 1, d);
    dt.setHours(0, 0, 0, 0);
    return dt;
  }

  function toISO(d) {
    return (
      d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0')
    );
  }

  /** "Apr 07, 2026" */
  function fmtDisplay(d) {
    return MONTHS_SHORT[d.getMonth()] + ' ' + String(d.getDate()).padStart(2, '0') + ', ' + d.getFullYear();
  }

  /** "04/07/2026" */
  function fmtMMDDYYYY(d) {
    return (
      String(d.getMonth() + 1).padStart(2, '0') + '/' + String(d.getDate()).padStart(2, '0') + '/' + d.getFullYear()
    );
  }

  /** "Tuesday, April 7, 2026" */
  function fmtLong(d) {
    return DAYS_LONG[d.getDay()] + ', ' + MONTHS_LONG[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
  }

  function isWeekend(d) {
    const day = d.getDay();
    return day === 0 || day === 6;
  }

  function prevWorkday(d) {
    const dt = new Date(d);
    do {
      dt.setDate(dt.getDate() - 1);
    } while (isWeekend(dt));
    return dt;
  }

  function nextWorkday(d) {
    const dt = new Date(d);
    const today = parseISO(TODAY_STR);
    do {
      dt.setDate(dt.getDate() + 1);
    } while (isWeekend(dt));
    return dt > today ? today : dt;
  }

  /** Current time as "HH:MM:SS" */
  function nowHMS() {
    const n = new Date();
    return (
      String(n.getHours()).padStart(2, '0') +
      ':' +
      String(n.getMinutes()).padStart(2, '0') +
      ':' +
      String(n.getSeconds()).padStart(2, '0')
    );
  }

  /* ═══════════════════════════════════════════════════════════════════════════
     HTML HELPERS
  ═══════════════════════════════════════════════════════════════════════════ */
  function makeInitials(name) {
    return (name ?? '')
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map(w => w[0].toUpperCase())
      .join('');
  }

  function escHtml(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function emptyState(msg) {
    return `<div class="att-empty-state py-4">
      <i class="ri-checkbox-circle-line text-success" style="font-size:2rem;"></i>
      <p class="text-muted mt-2 mb-0 small">${escHtml(msg)}</p>
    </div>`;
  }

  function emptyRow(cols, msg) {
    return `<tr><td colspan="${cols}">${emptyState(msg)}</td></tr>`;
  }

  function debounce(fn, wait) {
    let t;
    return function (...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), wait);
    };
  }
})(jQuery);
