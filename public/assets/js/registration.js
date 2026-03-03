// assets/js/registration.js
'use strict';

// ─────────────────────────────────────────────────────────────
//  CONSTANTS
// ─────────────────────────────────────────────────────────────

const TOTAL_STEPS = 5;

const EDU_LEVELS = [
  { id: 1, name: 'Primary Education', placeholder: 'e.g. San Lorenzo Elementary School' },
  { id: 2, name: 'Secondary Education', placeholder: 'e.g. Rizal National High School' },
  { id: 3, name: 'Vocational / Trade Course', placeholder: 'e.g. TESDA Training Center' },
  { id: 4, name: 'College', placeholder: 'e.g. University of the Philippines' },
  { id: 5, name: 'Graduate Studies', placeholder: 'e.g. Ateneo de Manila University' }
];

// ─────────────────────────────────────────────────────────────
//  STATE
// ─────────────────────────────────────────────────────────────

const state = {
  currentStep: 1,
  childIdx: 0,
  govIdCount: 0,
  nextEduLevel: 0,
  eduRowIdx: 0
};

// ─────────────────────────────────────────────────────────────
//  INIT
// ─────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
  const wizardData = document.getElementById('wizardData');
  if (wizardData) {
    state.childIdx = parseInt(wizardData.dataset.childIdx) || 0;
    state.govIdCount = parseInt(wizardData.dataset.govidIdx) || 0;
  }

  const eduMeta = document.getElementById('eduMeta');
  if (eduMeta) {
    const oldCount = parseInt(eduMeta.dataset.oldCount) || 0;
    state.nextEduLevel = Math.min(oldCount, EDU_LEVELS.length);
    state.eduRowIdx = oldCount;
  }

  initPanels();
  syncEduButton();
  bindButtons();
  Address.init();
});

function initPanels() {
  for (let i = 1; i <= TOTAL_STEPS; i++) {
    const panel = document.getElementById(`panel-${i}`);
    if (panel) panel.style.display = i === 1 ? 'block' : 'none';
  }
  document.getElementById('wzFill').style.width = '0%';
}

function bindButtons() {
  document.getElementById('addChildBtn')?.addEventListener('click', addChildRow);
  document.getElementById('addEduBtn')?.addEventListener('click', addEduRow);
  document.getElementById('addGovIdBtn')?.addEventListener('click', addGovIdRow);
}

// ─────────────────────────────────────────────────────────────
//  WIZARD NAVIGATION
// ─────────────────────────────────────────────────────────────

function changeStep(dir) {
  if (dir === 1 && !validateStep(state.currentStep)) return;
  const next = state.currentStep + dir;
  if (next < 1 || next > TOTAL_STEPS) return;
  if (next === TOTAL_STEPS) buildReview();
  goStep(next);
}

function goStep(step) {
  for (let i = 1; i <= TOTAL_STEPS; i++) {
    const panel = document.getElementById(`panel-${i}`);
    if (panel) {
      panel.classList.remove('active');
      panel.style.display = 'none';
    }
  }

  document.querySelectorAll('.wz-step').forEach(el => {
    const s = parseInt(el.dataset.step);
    el.classList.remove('active', 'completed');
    if (s < step) el.classList.add('completed');
    else if (s === step) el.classList.add('active');
  });

  state.currentStep = step;
  const target = document.getElementById(`panel-${step}`);
  if (target) {
    target.classList.add('active');
    target.style.display = 'block';
  }

  document.getElementById('wzFill').style.width = `${((step - 1) / (TOTAL_STEPS - 1)) * 100}%`;
  document.getElementById('prevBtn').style.display = step === 1 ? 'none' : 'inline-flex';
  document.getElementById('nextBtn').style.display = step === TOTAL_STEPS ? 'none' : 'inline-flex';
  document.getElementById('submitBtn').style.display = step === TOTAL_STEPS ? 'inline-flex' : 'none';

  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ─────────────────────────────────────────────────────────────
//  VALIDATION
// ─────────────────────────────────────────────────────────────

function validateStep(step) {
  const panel = document.getElementById(`panel-${step}`);
  const fields = panel.querySelectorAll('[required]');
  let valid = true;

  fields.forEach(field => {
    field.classList.remove('is-invalid');
    if (!field.value.trim()) {
      field.classList.add('is-invalid');
      valid = false;
    }
  });

  if (!valid) {
    panel.querySelector('.is-invalid')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
  return valid;
}

// ─────────────────────────────────────────────────────────────
//  EDUCATION
// ─────────────────────────────────────────────────────────────

function syncEduButton() {
  const btn = document.getElementById('addEduBtn');
  const label = document.getElementById('addEduBtnLabel');
  const hint = document.getElementById('addEduHint');
  if (!btn || !label) return;
  const done = state.nextEduLevel >= EDU_LEVELS.length;
  btn.style.display = done ? 'none' : 'inline-flex';
  label.textContent = done ? '' : `Add ${EDU_LEVELS[state.nextEduLevel].name}`;
  if (hint) hint.textContent = done ? '✓ All education levels have been added.' : '';
}

function addEduRow() {
  if (state.nextEduLevel >= EDU_LEVELS.length) return;
  const level = EDU_LEVELS[state.nextEduLevel];
  const idx = state.eduRowIdx;
  document.getElementById('educationContainer').insertAdjacentHTML('beforeend', buildEduRowHTML(level, idx));
  state.nextEduLevel++;
  state.eduRowIdx++;
  syncEduButton();
}

function removeEduRow(btn) {
  btn.closest('.edu-row').remove();
  const usedLevels = new Set(
    [...document.querySelectorAll('#educationContainer .edu-row')].map(row => parseInt(row.dataset.level))
  );
  const found = EDU_LEVELS.findIndex(l => !usedLevels.has(l.id));
  state.nextEduLevel = found === -1 ? EDU_LEVELS.length : found;
  syncEduButton();
}

function buildEduRowHTML(level, idx) {
  return `
    <div class="edu-row border rounded-2 p-3 mt-3" data-level="${level.id}">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <span class="edu-level-badge">
          <i class="ri ri-graduation-cap-line me-1"></i>${level.name}
        </span>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeEduRow(this)">
          <i class="ri ri-delete-bin-line me-1"></i> Remove
        </button>
      </div>
      <input type="hidden" name="education[${idx}][level_id]" value="${level.id}">
      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label">School / University Name <span class="text-danger">*</span></label>
          <input type="text" name="education[${idx}][school_name]" class="form-control"
            placeholder="${level.placeholder}" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Degree / Course</label>
          <input type="text" name="education[${idx}][degree_course]" class="form-control"
            placeholder="e.g. BS Computer Science">
        </div>
        <div class="col-md-3">
          <label class="form-label">From (Year)</label>
          <input type="number" name="education[${idx}][period_from]" class="form-control"
            placeholder="2010" min="1950" max="2099">
        </div>
        <div class="col-md-3">
          <label class="form-label">To (Year)</label>
          <input type="number" name="education[${idx}][period_to]" class="form-control"
            placeholder="2016" min="1950" max="2099">
        </div>
        <div class="col-md-3">
          <label class="form-label">Year Graduated</label>
          <input type="number" name="education[${idx}][year_graduated]" class="form-control"
            placeholder="2016" min="1950" max="2099">
        </div>
        <div class="col-md-3">
          <label class="form-label">Highest Level / Units</label>
          <input type="text" name="education[${idx}][highest_level_units]" class="form-control"
            placeholder="Completed">
        </div>
        <div class="col-md-6">
          <label class="form-label">Scholarship / Honors</label>
          <input type="text" name="education[${idx}][scholarship_honors]" class="form-control"
            placeholder="With Honors">
        </div>
      </div>
    </div>`;
}

// ─────────────────────────────────────────────────────────────
//  GOVERNMENT IDs
// ─────────────────────────────────────────────────────────────

function addGovIdRow() {
  const idx = state.govIdCount++;
  document.getElementById('govIdsContainer').insertAdjacentHTML(
    'beforeend',
    `
    <div class="govid-row row g-2 align-items-center mt-2">
      <div class="col-md-10">
        <input type="text" name="gov_ids[${idx}][name]" class="form-control"
          placeholder="e.g. PhilHealth No. 01-234567890-1">
      </div>
      <div class="col-md-2">
        <button type="button" class="btn btn-outline-danger btn-sm w-100"
          onclick="this.closest('.govid-row').remove()">
          <i class="ri ri-delete-bin-line"></i>
        </button>
      </div>
    </div>`
  );
}

// ─────────────────────────────────────────────────────────────
//  CHILDREN
// ─────────────────────────────────────────────────────────────

function addChildRow() {
  const idx = state.childIdx++;
  document.getElementById('childrenContainer').insertAdjacentHTML(
    'beforeend',
    `
    <div class="child-row row g-2 align-items-end mt-2">
      <div class="col-md-6">
        <label class="form-label">Child's Full Name</label>
        <input type="text" name="children[${idx}][child_name]" class="form-control" placeholder="Full Name">
      </div>
      <div class="col-md-4">
        <label class="form-label">Date of Birth</label>
        <input type="date" name="children[${idx}][date_of_birth]" class="form-control">
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button type="button" class="btn btn-outline-danger btn-sm w-100"
          onclick="this.closest('.child-row').remove()">
          <i class="ri ri-delete-bin-line me-1"></i> Remove
        </button>
      </div>
    </div>`
  );
}

// ─────────────────────────────────────────────────────────────
//  COPY ADDRESS — "same as permanent" checkbox
// ─────────────────────────────────────────────────────────────

function copyAddress(checkbox) {
  if (checkbox.checked) {
    Address.copyPermToCurr();
  } else {
    Address.resetBlock('curr');
  }
}

// ─────────────────────────────────────────────────────────────
//  REVIEW
// ─────────────────────────────────────────────────────────────

function buildReview() {
  const val = name => document.querySelector(`[name="${name}"]`)?.value?.trim() || '—';
  const sel = name => {
    const el = document.querySelector(`[name="${name}"]`);
    return el?.options?.[el.selectedIndex]?.text || el?.value || '—';
  };

  // Build a formatted address string from hidden inputs
  const addr = prefix => {
    const parts = [
      val(`${prefix}[house_number]`),
      val(`${prefix}[street]`),
      val(`${prefix}[subdivision]`),
      val(`${prefix}[barangay]`),
      val(`${prefix}[city]`),
      val(`${prefix}[province]`),
      val(`${prefix}[region]`)
    ].filter(v => v && v !== '—');
    return parts.join(', ') || '—';
  };

  const mid = val('middle_name') !== '—' ? `${val('middle_name')} ` : '';

  setHTML(
    'rv-personal',
    `
    <strong>${val('first_name')} ${mid}${val('last_name')}</strong><br>
    Emp No: ${val('employee_number')}<br>
    DOB: ${val('date_of_birth')} &nbsp;|&nbsp; Gender: ${sel('gender')}<br>
    Civil Status: ${sel('civil_status')} &nbsp;|&nbsp; Blood Type: ${sel('blood_type')}<br>
    Mobile: ${val('mobile_number')}<br>
    Citizenship: ${val('citizenship')}
  `
  );

  setHTML(
    'rv-address',
    `
    <strong>Permanent:</strong><br>${addr('permanent')}<br><br>
    <strong>Current:</strong><br>${addr('current')}
  `
  );

  setHTML(
    'rv-family',
    `
    Father: ${val('family[father_name]')}<br>
    Mother: ${val('family[mother_name]')}<br>
    Spouse: ${val('family[spouse_name]')}<br>
    <strong>Emergency:</strong> ${val('family[emergency_contact_name]')} — ${val('family[emergency_contact_number]')}
  `
  );

  const eduLines = [...document.querySelectorAll('#educationContainer .edu-row')].map(row => {
    const levelId = parseInt(row.dataset.level);
    const levelName = EDU_LEVELS.find(l => l.id === levelId)?.name || 'Education';
    const school = row.querySelector('[name$="[school_name]"]')?.value || '—';
    return `• <strong>${levelName}:</strong> ${school}`;
  });

  const govLines = [...document.querySelectorAll('#govIdsContainer .govid-row input[type="text"]')]
    .map(el => el.value)
    .filter(Boolean)
    .map(v => `• ${v}`);

  setHTML(
    'rv-education',
    `
    <strong>Education:</strong><br>
    ${eduLines.length ? eduLines.join('<br>') : '—'}
    <br><br>
    <strong>Gov IDs:</strong><br>
    ${govLines.length ? govLines.join('<br>') : '—'}
  `
  );
}

function setHTML(id, html) {
  const el = document.getElementById(id);
  if (el) el.innerHTML = html;
}

// ─────────────────────────────────────────────────────────────
//  ADDRESS MODULE — PSGC Cascading + Strict Unlock Chain
//
//  Unlock order per address block:
//    1. Region   (always available on load)
//    2. Province (unlocks when Region is chosen)
//    3. City     (unlocks when Province is chosen)
//    4. Barangay (unlocks when City is chosen)
//    5. Street, Subdivision, House No., ZIP
//       (all unlock together when Barangay is chosen)
//
//  Clearing a field re-locks everything below it.
//
//  Proxy routes (web.php):
//    GET /admin/psgc/regions
//    GET /admin/psgc/regions/{code}/provinces
//    GET /admin/psgc/provinces/{code}/cities
//    GET /admin/psgc/cities/{code}/barangays
// ─────────────────────────────────────────────────────────────

const Address = (() => {
  'use strict';

  const BASE = '/admin/psgc';

  // In-memory fetch cache — avoids re-hitting the proxy
  const cache = {};

  // TomSelect instances keyed by element id
  const ts = {};

  // ── Fetch with cache ──────────────────────────────────────
  async function apiFetch(url) {
    if (cache[url]) return cache[url];
    const res = await fetch(url);
    if (!res.ok) throw new Error(`PSGC ${url} → ${res.status}`);
    cache[url] = await res.json();
    return cache[url];
  }

  // ── Create TomSelect on a <select> ────────────────────────
  function makeTs(id) {
    if (ts[id]) {
      ts[id].destroy();
      delete ts[id];
    }
    const el = document.getElementById(id);
    if (!el) return null;
    ts[id] = new TomSelect(el, {
      placeholder: el.querySelector('option')?.textContent || '— Select —',
      allowEmptyOption: true,
      maxOptions: 5000,
      searchField: ['text'],
      plugins: ['clear_button']
    });
    return ts[id];
  }

  // ── Fill a TomSelect with PSGC items ─────────────────────
  function populate(id, items, placeholder) {
    const inst = ts[id];
    if (!inst) return;
    inst.clear(true);
    inst.clearOptions();
    inst.addOption({ value: '', text: placeholder });
    items.forEach(item => inst.addOption({ value: item.code, text: item.name }));
    inst.enable();
    inst.setValue('', true);
    inst.refreshOptions(false);
  }

  // ── Disable a select and clear it back to placeholder ─────
  function lockSelect(id, placeholder) {
    const inst = ts[id];
    if (!inst) return;
    inst.clear(true);
    inst.clearOptions();
    inst.addOption({ value: '', text: placeholder });
    inst.setValue('', true);
    inst.disable();
  }

  // ── Disable a plain text input and clear its value ────────
  function lockInput(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.value = '';
    el.disabled = true;
    el.readOnly = false;
    el.classList.remove('is-invalid');
  }

  // ── Enable a plain text input ─────────────────────────────
  function unlockInput(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.disabled = false;
  }

  // ── Write to hidden name input ────────────────────────────
  function setHidden(id, value) {
    const el = document.getElementById(id);
    if (el) el.value = value ?? '';
  }

  // ── Get displayed label from a TomSelect value ────────────
  function getLabel(id, code) {
    if (!code || !ts[id]) return '';
    return ts[id].getItem(code)?.textContent?.trim() || '';
  }

  // ── Lock all fields below barangay for a prefix ───────────
  function lockFreeTextFields(prefix) {
    ['street', 'subdivision', 'house_number', 'zip_code'].forEach(f => lockInput(`${prefix}_${f}`));
  }

  // ── Unlock all fields below barangay for a prefix ────────
  function unlockFreeTextFields(prefix) {
    ['street', 'subdivision', 'house_number', 'zip_code'].forEach(f => unlockInput(`${prefix}_${f}`));
  }

  // ── Wire one full address block ───────────────────────────
  function wireBlock(prefix) {
    const id = {
      region: `${prefix}_region`,
      province: `${prefix}_province`,
      city: `${prefix}_city`,
      barangay: `${prefix}_barangay`
    };

    // Create TomSelect on each dropdown
    makeTs(id.region);
    makeTs(id.province);
    makeTs(id.city);
    makeTs(id.barangay);

    // Province / City / Barangay start locked
    ts[id.province]?.disable();
    ts[id.city]?.disable();
    ts[id.barangay]?.disable();

    // Free-text fields (street → zip) start locked
    lockFreeTextFields(prefix);

    // ── Load regions on mount ─────────────────────────────
    apiFetch(`${BASE}/regions`)
      .then(regions => {
        populate(id.region, regions, '— Select Region —');

        // Restore after validation failure
        const savedCode = document.getElementById(`${prefix}_region_code`)?.value;
        if (savedCode) {
          ts[id.region]?.setValue(savedCode, true);
          setHidden(`${prefix}_region_name`, getLabel(id.region, savedCode));
          loadProvinces(prefix, id, savedCode);
        }
      })
      .catch(() => showFetchError(id.region));

    // ── Region selected → load Provinces ─────────────────
    ts[id.region]?.on('change', regionCode => {
      // Lock everything below
      lockSelect(id.province, '— Select Province —');
      lockSelect(id.city, '— Select City / Municipality —');
      lockSelect(id.barangay, '— Select Barangay —');
      lockFreeTextFields(prefix);

      setHidden(`${prefix}_region_code`, regionCode);
      setHidden(`${prefix}_region_name`, getLabel(id.region, regionCode));
      setHidden(`${prefix}_province_code`, '');
      setHidden(`${prefix}_province_name`, '');
      setHidden(`${prefix}_city_code`, '');
      setHidden(`${prefix}_city_name`, '');
      setHidden(`${prefix}_barangay_name`, '');

      if (!regionCode) return;
      loadProvinces(prefix, id, regionCode);
    });

    // ── Province selected → load Cities ──────────────────
    ts[id.province]?.on('change', provinceCode => {
      lockSelect(id.city, '— Select City / Municipality —');
      lockSelect(id.barangay, '— Select Barangay —');
      lockFreeTextFields(prefix);

      setHidden(`${prefix}_province_code`, provinceCode);
      setHidden(`${prefix}_province_name`, getLabel(id.province, provinceCode));
      setHidden(`${prefix}_city_code`, '');
      setHidden(`${prefix}_city_name`, '');
      setHidden(`${prefix}_barangay_name`, '');

      if (!provinceCode) return;
      loadCities(prefix, id, provinceCode);
    });

    // ── City selected → load Barangays ────────────────────
    ts[id.city]?.on('change', cityCode => {
      lockSelect(id.barangay, '— Select Barangay —');
      lockFreeTextFields(prefix);

      setHidden(`${prefix}_city_code`, cityCode);
      setHidden(`${prefix}_city_name`, getLabel(id.city, cityCode));
      setHidden(`${prefix}_barangay_name`, '');

      if (!cityCode) return;
      loadBarangays(prefix, id, cityCode);
    });

    // ── Barangay selected → unlock free-text fields ───────
    ts[id.barangay]?.on('change', barangayCode => {
      setHidden(`${prefix}_barangay_name`, getLabel(id.barangay, barangayCode));

      if (barangayCode) {
        unlockFreeTextFields(prefix); // street / subdivision / house / zip now open
      } else {
        lockFreeTextFields(prefix);
      }
    });
  }

  // ── Fetch helpers ─────────────────────────────────────────
  function loadProvinces(prefix, id, regionCode) {
    apiFetch(`${BASE}/regions/${regionCode}/provinces`)
      .then(data => {
        populate(id.province, data, '— Select Province —');
        const saved = document.getElementById(`${prefix}_province_code`)?.value;
        if (saved) {
          ts[id.province]?.setValue(saved, true);
          setHidden(`${prefix}_province_name`, getLabel(id.province, saved));
          loadCities(prefix, id, saved);
        }
      })
      .catch(() => showFetchError(id.province));
  }

  function loadCities(prefix, id, provinceCode) {
    apiFetch(`${BASE}/provinces/${provinceCode}/cities`)
      .then(data => {
        populate(id.city, data, '— Select City / Municipality —');
        const saved = document.getElementById(`${prefix}_city_code`)?.value;
        if (saved) {
          ts[id.city]?.setValue(saved, true);
          setHidden(`${prefix}_city_name`, getLabel(id.city, saved));
          loadBarangays(prefix, id, saved);
        }
      })
      .catch(() => showFetchError(id.city));
  }

  function loadBarangays(prefix, id, cityCode) {
    apiFetch(`${BASE}/cities/${cityCode}/barangays`)
      .then(data => {
        populate(id.barangay, data, '— Select Barangay —');
        const savedName = document.getElementById(`${prefix}_barangay_name`)?.value;
        if (savedName) {
          const match = data.find(b => b.name === savedName);
          if (match) {
            ts[id.barangay]?.setValue(match.code, true);
            unlockFreeTextFields(prefix); // restore free-text fields on validation recovery
          }
        }
      })
      .catch(() => showFetchError(id.barangay));
  }

  function showFetchError(selectId) {
    const wrapper = document.getElementById(selectId)?.closest('.col-md-6, .col-md-4, .col-md-3');
    if (wrapper && !wrapper.querySelector('.psgc-error')) {
      wrapper.insertAdjacentHTML(
        'beforeend',
        '<small class="text-danger psgc-error">Failed to load. Check your connection.</small>'
      );
    }
  }

  // ── Public: copy permanent → current (checkbox) ───────────
  function copyPermToCurr() {
    const levels = ['region', 'province', 'city', 'barangay'];

    levels.forEach(level => {
      const srcTs = ts[`perm_${level}`];
      const dstTs = ts[`curr_${level}`];
      if (!srcTs || !dstTs) return;

      const srcVal = srcTs.getValue();

      // Mirror all options + current value into destination
      dstTs.clear(true);
      dstTs.clearOptions();
      Object.values(srcTs.options || {}).forEach(opt => dstTs.addOption({ value: opt.value, text: opt.text }));
      dstTs.enable();
      dstTs.setValue(srcVal, true);
      dstTs.disable();

      // Mirror hidden code + name inputs
      setHidden(`curr_${level}_code`, document.getElementById(`perm_${level}_code`)?.value);
      setHidden(`curr_${level}_name`, document.getElementById(`perm_${level}_name`)?.value);
    });

    // Copy + lock free-text fields
    ['street', 'subdivision', 'house_number', 'zip_code'].forEach(f => {
      const src = document.querySelector(`[name="permanent[${f}]"]`);
      const dst = document.getElementById(`curr_${f}`);
      if (!src || !dst) return;
      dst.value = src.value;
      dst.disabled = true;
      dst.readOnly = true;
    });

    // Unlock curr free-text only if barangay was already chosen on permanent side
    const permBrgy = document.getElementById('perm_barangay_name')?.value;
    if (!permBrgy) {
      lockFreeTextFields('curr');
    }
  }

  // ── Public: fully reset one address block ─────────────────
  function resetBlock(prefix) {
    lockSelect(`${prefix}_province`, '— Select Province —');
    lockSelect(`${prefix}_city`, '— Select City / Municipality —');
    lockSelect(`${prefix}_barangay`, '— Select Barangay —');
    lockFreeTextFields(prefix);

    ['region', 'province', 'city'].forEach(l => {
      setHidden(`${prefix}_${l}_code`, '');
      setHidden(`${prefix}_${l}_name`, '');
    });
    setHidden(`${prefix}_barangay_name`, '');

    // Reload regions so the block is still usable after unchecking "same as permanent"
    apiFetch(`${BASE}/regions`)
      .then(regions => populate(`${prefix}_region`, regions, '— Select Region —'))
      .catch(() => {});

    // Unlock the region select itself
    ts[`${prefix}_region`]?.enable();
    ts[`${prefix}_region`]?.setValue('', true);
    setHidden(`${prefix}_region_code`, '');
    setHidden(`${prefix}_region_name`, '');

    // Clear free-text field values
    ['street', 'subdivision', 'house_number', 'zip_code'].forEach(f => {
      const el = document.getElementById(`${prefix}_${f}`);
      if (el) {
        el.value = '';
        el.disabled = true;
        el.readOnly = false;
      }
    });
  }

  // ── Public init ───────────────────────────────────────────
  function init() {
    wireBlock('perm');
    wireBlock('curr');
  }

  return { init, copyPermToCurr, resetBlock };
})();
