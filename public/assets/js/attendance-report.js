/**
 * attendance-report.js
 * ─────────────────────────────────────────────────────────────────────────────
 * Daily attendance report — client-side logic.
 *
 * Sections handled:  Summary cards  |  Late Arrivals  |  On Leave
 * Removed:           Absent list (dropped per updated requirements)
 *
 * Features:
 *   • Prev/Next/Today day navigation — weekends auto-skipped both directions.
 *   • Client-side weekend/future guard — no AJAX fired for invalid dates.
 *   • Notice panel replaces data panels for weekend/future/error states.
 *   • Shimmer skeleton loaders during fetch.
 *   • In-flight XHR aborted on rapid navigation.
 *   • Inline Retry button on network/server failure.
 *   • Stat-card numbers animate (ease-out cubic) on each data update.
 *   • Live employee search + section filter (debounced 200 ms).
 *   • All dynamic HTML sanitised via escHtml() (XSS-safe).
 *
 * Requires: jQuery ≥ 3.x  |  window.AttConfig injected by Blade
 * ─────────────────────────────────────────────────────────────────────────────
 */
(function ($) {
  'use strict';

  /* ═══════════════════════════════════════════════════════════════════════════
     CONFIG  — injected by Blade so URLs never need to be hardcoded here
  ═══════════════════════════════════════════════════════════════════════════ */
  const CFG = window.AttConfig ?? {};
  const ROUTE_DAILY = CFG.routes?.daily ?? '/admin/attendance-report/daily';
  const TODAY_STR = CFG.today ?? toISO(new Date());

  /* ═══════════════════════════════════════════════════════════════════════════
     STATE
  ═══════════════════════════════════════════════════════════════════════════ */
  let currentDate = parseISO(CFG.initialDate ?? TODAY_STR);
  let activeXHR = null;

  /* ═══════════════════════════════════════════════════════════════════════════
     INIT
  ═══════════════════════════════════════════════════════════════════════════ */
  $(function () {
    injectShimmerKeyframe();
    bindDayNav();
    bindFilters();
    refreshDateLabel();
    // First paint is server-rendered — no AJAX needed on initial load.
  });

  /* ═══════════════════════════════════════════════════════════════════════════
     DAY NAVIGATION
  ═══════════════════════════════════════════════════════════════════════════ */

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
    $('#dateLabel').text(fmtDisplay(currentDate));
  }

  /* ═══════════════════════════════════════════════════════════════════════════
     AJAX
  ═══════════════════════════════════════════════════════════════════════════ */

  function fetchDailyData() {
    // Cancel any previous in-flight request.
    if (activeXHR) {
      activeXHR.abort();
      activeXHR = null;
    }

    const dateStr = toISO(currentDate);

    // ── Client-side guards (no network round-trip needed) ─────────────────
    if (isWeekend(currentDate)) {
      showNotice('weekend', 'Attendance is not recorded on weekends (Saturday & Sunday).');
      return;
    }
    if (dateStr > TODAY_STR) {
      showNotice('future', 'No available data yet for this date.');
      return;
    }

    // ── Valid workday — fetch from server ─────────────────────────────────
    showSkeletons();

    activeXHR = $.ajax({
      url: ROUTE_DAILY,
      method: 'GET',
      data: { date: dateStr },
      dataType: 'json'
    })
      .done(function (res) {
        if (!res.success) {
          // Server-side validation matched a known reason.
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
        applyFilters(); // re-apply any active search / section filter
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
    // Animate each stat key that has a matching [data-stat] element.
    Object.entries(summary).forEach(([key, value]) => {
      animateNumber(`[data-stat="${key}"]`, value);
    });
  }

  // ── Late Arrivals ──────────────────────────────────────────────────────────

  function renderLate(list) {
    $('#count-late').text(list.length);
    const $tbody = $('#late-list').empty();

    if (list.length === 0) {
      $tbody.html(`
        <tr>
          <td colspan="4">
            <div class="att-empty" style="padding:2rem;">
              <i class="ri ri-checkbox-circle-line" style="font-size:2rem;color:var(--att-present,#28a745);"></i>
              <p style="color:var(--att-muted);margin-top:.5rem;font-size:.83rem;">No late arrivals on this day.</p>
            </div>
          </td>
        </tr>
      `);
      return;
    }

    list.forEach(function (emp) {
      $tbody.append(`
        <tr class="daily-searchable att-tr-hover"
            data-name="${emp.name.toLowerCase()}"
            data-group="late">
          <td style="padding:.9rem 1.25rem;">
            <div class="d-flex align-items-center gap-2">
              <div class="emp-av av-c${emp.av}" style="width:34px;height:34px;font-size:.73rem;">
                ${makeInitials(emp.name)}
              </div>
              <div>
                <div class="emp-name">${escHtml(emp.name)}</div>
                <div class="emp-pos">${escHtml(emp.pos)} · ${escHtml(emp.dept)}</div>
              </div>
            </div>
          </td>
          <td class="att-td-center fw-semibold">${escHtml(emp.time_in)}</td>
          <td class="att-td-center">
            <span class="att-badge b-late">
              <i class="ri ri-time-line"></i>${escHtml(emp.late_duration)}
            </span>
          </td>
          <td class="att-td-center">
            <span class="att-badge" style="background:#fff3cd;color:#856404;font-size:.7rem;">Late</span>
          </td>
        </tr>
      `);
    });
  }

  // ── On Leave ───────────────────────────────────────────────────────────────

  const LEAVE_PALETTE = {
    sick: { color: 'var(--att-sick)', bg: 'var(--att-sick-soft)' },
    incentive: { color: 'var(--att-sil)', bg: 'var(--att-sil-soft)' },
    vacation: { color: 'var(--att-vl)', bg: 'var(--att-vl-soft)' }
  };

  function leaveColors(type) {
    const t = (type ?? '').toLowerCase();
    if (t.includes('sick')) return LEAVE_PALETTE.sick;
    if (t.includes('incentive')) return LEAVE_PALETTE.incentive;
    return LEAVE_PALETTE.vacation;
  }

  function renderOnLeave(list) {
    $('#count-leave').text(list.length);
    const $container = $('#leave-list').empty();

    if (list.length === 0) {
      $container.html(`
        <div class="att-empty" style="padding:2rem;">
          <i class="ri ri-checkbox-circle-line" style="font-size:2rem;color:var(--att-present,#28a745);"></i>
          <p style="color:var(--att-muted);margin-top:.5rem;font-size:.83rem;">No employees on leave on this day.</p>
        </div>
      `);
      return;
    }

    list.forEach(function (emp) {
      const { color, bg } = leaveColors(emp.leave_type);
      const plural = emp.leave_days > 1 ? 's' : '';

      $container.append(`
        <div class="daily-emp-row daily-searchable"
             data-name="${emp.name.toLowerCase()}"
             data-group="leave">
          <div class="emp-av av-c${emp.av}">${makeInitials(emp.name)}</div>
          <div class="daily-emp-info">
            <div class="daily-emp-name">${escHtml(emp.name)}</div>
            <div class="daily-emp-meta">${escHtml(emp.pos)} · ${escHtml(emp.dept)}</div>
          </div>
          <div class="text-end" style="flex-shrink:0;">
            <span class="att-badge" style="background:${bg};color:${color};">
              ${escHtml(emp.leave_type)}
            </span>
            <div style="font-size:.7rem;color:var(--att-muted);margin-top:3px;">
              ${emp.leave_days} day${plural}
            </div>
          </div>
        </div>
      `);
    });
  }

  /* ═══════════════════════════════════════════════════════════════════════════
     SKELETON LOADERS
  ═══════════════════════════════════════════════════════════════════════════ */

  function injectShimmerKeyframe() {
    if (document.getElementById('att-shimmer-kf')) return;
    $(
      '<style id="att-shimmer-kf">@keyframes att-shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}</style>'
    ).appendTo('head');
  }

  const SHIMMER =
    'height:54px;border-radius:8px;margin:.4rem 0;' +
    'background:linear-gradient(90deg,#f0f0f5 25%,#e8e8f0 50%,#f0f0f5 75%);' +
    'background-size:200% 100%;animation:att-shimmer 1.2s infinite;';

  function shimmer() {
    return `<div style="${SHIMMER}"></div>`;
  }

  function showSkeletons() {
    hideNotice();
    // Late table: wrap shimmer rows in a full-width td.
    $('#late-list').html(
      `<tr><td colspan="4" style="padding:.5rem 1rem;">${Array(4).fill(shimmer()).join('')}</td></tr>`
    );
    // Leave list: direct shimmer divs.
    $('#leave-list').html(Array(3).fill(shimmer()).join(''));
    // Reset counts while loading.
    $('#count-late, #count-leave').text('…');
  }

  /* ═══════════════════════════════════════════════════════════════════════════
     NOTICE PANEL  (weekend / future / error)
  ═══════════════════════════════════════════════════════════════════════════ */

  const NOTICE_ICONS = {
    weekend: 'ri-calendar-close-line',
    future: 'ri-hourglass-line',
    server_error: 'ri-error-warning-line'
  };

  function showNotice(reason, message) {
    const icon = NOTICE_ICONS[reason] ?? 'ri-information-line';

    $('#att-notice-body').html(`
      <i class="ri ${icon}" style="font-size:2.8rem;color:var(--att-muted);"></i>
      <p style="color:var(--att-muted);margin-top:.85rem;font-size:.95rem;max-width:340px;text-align:center;">
        ${escHtml(message)}
      </p>
    `);

    $('#att-notice').show();
    $('#att-data-panels, #att-summary-cards').hide();

    // Clear stat cards.
    $('[data-stat]').text('—');
  }

  function hideNotice() {
    $('#att-notice').hide();
    $('#att-data-panels, #att-summary-cards').show();
  }

  function showErrorBanner(message) {
    const html = `
      <div class="att-empty" style="color:var(--att-absent);padding:1.75rem;">
        <i class="ri ri-error-warning-line" style="font-size:2rem;"></i>
        <p style="margin:.5rem 0 .75rem;">${escHtml(message)}</p>
        <button class="att-btn" id="btnRetry" style="font-size:.78rem;padding:.35rem .9rem;">
          <i class="ri ri-refresh-line"></i> Retry
        </button>
      </div>
    `;
    $('#late-list').html(`<tr><td colspan="4">${html}</td></tr>`);
    $('#leave-list').html(html);
    // One-time retry handler via delegation.
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

    const STEPS = 20;
    const MS = 350 / STEPS;
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

    $('.daily-searchable').each(function () {
      const $el = $(this);
      const name = $el.data('name') ?? '';
      const group = $el.data('group') ?? '';

      const matchQ = !q || name.includes(q);
      const matchF = filter === 'all' || filter === group;

      $el.toggle(matchQ && matchF);
    });

    // Show/hide entire section columns based on filter.
    $('#section-late').toggle(filter === 'all' || filter === 'late');
    $('#section-leave').toggle(filter === 'all' || filter === 'leave');
  }

  /* ═══════════════════════════════════════════════════════════════════════════
     DATE HELPERS
  ═══════════════════════════════════════════════════════════════════════════ */

  const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

  /** Parse 'YYYY-MM-DD' safely without timezone offset. */
  function parseISO(str) {
    const [y, m, d] = str.split('-').map(Number);
    const dt = new Date(y, m - 1, d);
    dt.setHours(0, 0, 0, 0);
    return dt;
  }

  /** Format Date as 'YYYY-MM-DD'. */
  function toISO(d) {
    return (
      d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0')
    );
  }

  /** Format Date as 'Apr 07, 2026'. */
  function fmtDisplay(d) {
    return MONTHS[d.getMonth()] + ' ' + String(d.getDate()).padStart(2, '0') + ', ' + d.getFullYear();
  }

  function isWeekend(d) {
    const day = d.getDay();
    return day === 0 || day === 6;
  }

  /** Move backward one calendar day, skip weekends. */
  function prevWorkday(d) {
    const dt = new Date(d);
    do {
      dt.setDate(dt.getDate() - 1);
    } while (isWeekend(dt));
    return dt;
  }

  /** Move forward one calendar day, skip weekends — but never past today. */
  function nextWorkday(d) {
    const dt = new Date(d);
    const today = parseISO(TODAY_STR);

    do {
      dt.setDate(dt.getDate() + 1);
    } while (isWeekend(dt));

    // Do not allow navigating past today.
    return dt > today ? today : dt;
  }

  /* ═══════════════════════════════════════════════════════════════════════════
     UTILITIES
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

  function debounce(fn, wait) {
    let t;
    return function (...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), wait);
    };
  }
})(jQuery);
