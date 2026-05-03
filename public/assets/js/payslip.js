/**
 * payslip.js
 * ─────────────────────────────────────────────────────────────
 * Payslip UI interactions + backend wiring (Laravel routes).
 *
 * Features:
 *  • Employee/year/month filtering (reloads with query params)
 *  • Cutoff tab switching
 *  • PDF open/download via backend route
 *  • Send Email (AJAX POST) + toast feedback
 *  • Lightweight toast fallback (uses toastr if available)
 * ─────────────────────────────────────────────────────────────
 */

'use strict';

function psConfig() {
  return window.PS_CONFIG || {};
}

/* ════════════════════════════════════════════════════════════
   TOAST
════════════════════════════════════════════════════════════ */
(function () {
  var toastTimer;

  window.psShowToast = function (msg, type) {
    type = type || 'success';
    var icons = {
      success: 'ri-checkbox-circle-line',
      info: 'ri-information-line',
      error: 'ri-error-warning-line'
    };

    var toast = document.getElementById('psToast');
    if (!toast) return;

    if (window.toastr) {
      window.toastr[type === 'error' ? 'error' : type === 'info' ? 'info' : 'success'](msg);
      return;
    }

    toast.className = 'ps-toast ' + type;
    toast.innerHTML = '<i class="ri ' + (icons[type] || icons.success) + '" style="font-size:16px;"></i> ' + msg;

    clearTimeout(toastTimer);
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        toast.classList.add('show');
      });
    });

    toastTimer = setTimeout(function () {
      toast.classList.remove('show');
    }, 3400);
  };
})();

/* ════════════════════════════════════════════════════════════
   FILTERS (reload with query params)
════════════════════════════════════════════════════════════ */
(function () {
  var config = psConfig();
  var employeeSel = document.getElementById('psSelectEmployee');
  var yearSel = document.getElementById('psYear');
  var monthSel = document.getElementById('psMonth');

  function currentYear() {
    return yearSel ? yearSel.value : config.year || '';
  }
  function currentMonth() {
    return monthSel ? monthSel.value : config.month || '';
  }
  function currentEmployeeId() {
    if (config.canSelectEmployee) return (employeeSel && employeeSel.value) || '';
    return config.selectedEmployeeId || '';
  }

  function buildIndexUrl() {
    var baseUrl = config.indexUrl || window.location.pathname;
    var url = new URL(baseUrl, window.location.origin);

    var id = currentEmployeeId();
    var yr = currentYear();
    var mo = currentMonth();

    if (id) url.searchParams.set('employee_id', id);
    else url.searchParams.delete('employee_id');

    if (yr) url.searchParams.set('year', yr);
    if (mo) url.searchParams.set('month', mo);

    return url.toString();
  }

  function syncProcessHidden() {
    var formEmployee = document.getElementById('psProcessEmployeeId');
    var formYear = document.getElementById('psProcessYear');
    var formMonth = document.getElementById('psProcessMonth');
    var btn = document.getElementById('psBtnProcess');

    var id = currentEmployeeId();
    var yr = currentYear();
    var mo = currentMonth();

    if (formEmployee) formEmployee.value = id || '';
    if (formYear) formYear.value = yr || '';
    if (formMonth) formMonth.value = mo || '';

    if (btn) {
      btn.disabled = config.canSelectEmployee ? !id : !config.selectedEmployeeId;
    }
  }

  function onChangeReload() {
    syncProcessHidden();
    window.location.href = buildIndexUrl();
  }

  if (employeeSel) employeeSel.addEventListener('change', onChangeReload);
  if (yearSel) yearSel.addEventListener('change', onChangeReload);
  if (monthSel) monthSel.addEventListener('change', onChangeReload);

  syncProcessHidden();
})();

/* ════════════════════════════════════════════════════════════
   CUTOFF TABS
════════════════════════════════════════════════════════════ */
(function () {
  var tabBtns = document.querySelectorAll('.ps-tab-btn');
  var tabPanels = document.querySelectorAll('.ps-tab-panel');

  if (!tabBtns.length) return;

  tabBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var target = btn.dataset.tab;

      tabBtns.forEach(function (b) {
        b.classList.remove('active');
      });
      tabPanels.forEach(function (p) {
        p.classList.remove('active');
      });

      btn.classList.add('active');
      var panel = document.getElementById('tab-' + target);
      if (panel) panel.classList.add('active');
    });
  });
})();

/* ════════════════════════════════════════════════════════════
   EMAIL MODAL (open)
════════════════════════════════════════════════════════════ */
(function () {
  function openEmailModal() {
    var modalEl = document.getElementById('emailModal');
    if (!modalEl || !window.bootstrap) return;
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  }

  var btn1 = document.getElementById('btnEmail');
  var btn2 = document.getElementById('btnEmail2');

  if (btn1) btn1.addEventListener('click', openEmailModal);
  if (btn2) btn2.addEventListener('click', openEmailModal);
})();

/* ════════════════════════════════════════════════════════════
   SEND EMAIL (AJAX)
════════════════════════════════════════════════════════════ */
(function () {
  var config = psConfig();
  var form = document.getElementById('psEmailForm');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    if (!config.emailUrl) {
      window.psShowToast('Email route is not configured.', 'error');
      return;
    }

    var btn = document.getElementById('btnSendEmail');
    var fd = new FormData(form);

    // Keep hidden period fields in sync with current filters
    var yearSel = document.getElementById('psYear');
    var monthSel = document.getElementById('psMonth');
    if (yearSel) fd.set('year', yearSel.value);
    if (monthSel) fd.set('month', monthSel.value);

    if (btn) {
      btn.disabled = true;
      btn.dataset.originalHtml = btn.innerHTML;
      btn.innerHTML = '<i class="ri ri-loader-4-line me-1"></i> Sending…';
    }

    fetch(config.emailUrl, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
      body: fd
    })
      .then(function (res) {
        return res.json().then(function (json) {
          return { ok: res.ok, status: res.status, json: json };
        });
      })
      .then(function (payload) {
        if (!payload.ok) {
          var msg = (payload.json && payload.json.message) || 'Failed to send email.';
          window.psShowToast(msg, 'error');
          return;
        }

        var modalEl = document.getElementById('emailModal');
        if (modalEl && window.bootstrap) {
          bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        }

        window.psShowToast(payload.json.message || 'Payslip emailed successfully.', 'success');
      })
      .catch(function () {
        window.psShowToast('Network error while sending email.', 'error');
      })
      .finally(function () {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = btn.dataset.originalHtml || btn.innerHTML;
        }
      });
  });
})();

/* ════════════════════════════════════════════════════════════
   ANIMATE NUMBERS ON LOAD (subtle count-up for sidebar)
════════════════════════════════════════════════════════════ */
(function () {
  function animateValue(el, start, end, duration) {
    var startTime = null;
    function step(timestamp) {
      if (!startTime) startTime = timestamp;
      var progress = Math.min((timestamp - startTime) / duration, 1);
      var eased = 1 - Math.pow(1 - progress, 3);
      var current = start + (end - start) * eased;
      el.textContent =
        '₱' +
        current.toLocaleString('en-PH', {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        });
      if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  var heroEl = document.querySelector('.ps-net-hero-amount');
  if (heroEl) {
    setTimeout(function () {
      var end = parseFloat(heroEl.getAttribute('data-amount') || '0');
      animateValue(heroEl, 0, isNaN(end) ? 0 : end, 900);
    }, 300);
  }
})();

(function () {
  var btn = document.getElementById('btnPrintPayslip');
  if (!btn) return;

  btn.addEventListener('click', function (e) {
    e.preventDefault();

    var config = window.PS_CONFIG || {};

    if (!config.pdfUrl) {
      window.psShowToast('PDF route is not configured.', 'error');
      return;
    }

    var url = new URL(config.pdfUrl, window.location.origin);

    var yearSel = document.getElementById('psYear');
    var monthSel = document.getElementById('psMonth');
    var employeeSel = document.getElementById('psSelectEmployee');

    var yr = (yearSel && yearSel.value) || config.year || '';
    var mo = (monthSel && monthSel.value) || config.month || '';

    if (yr) url.searchParams.set('year', yr);
    if (mo) url.searchParams.set('month', mo);

    if (config.canSelectEmployee) {
      var employeeId = (employeeSel && employeeSel.value) || '';
      if (employeeId) url.searchParams.set('employee_id', employeeId);
    }

    url.searchParams.set('download', '0');
    url.searchParams.set('_t', Date.now()); // 🔥 ADD THIS LINE HERE

    window.open(url.toString(), '_blank', 'noopener');
  });
})();
