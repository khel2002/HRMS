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

const REVIEW_SECTION_LABELS = [
  { icon: 'ri-user-3-line', text: 'Personal Information' },
  { icon: 'ri-map-pin-line', text: 'Address Information' },
  { icon: 'ri-team-line', text: 'Family Background' },
  { icon: 'ri-book-open-line', text: 'Education & Government IDs' }
];

const NO_SPOUSE_STATUSES = ['single', 'widow'];

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
    const n = parseInt(eduMeta.dataset.oldCount) || 0;
    state.nextEduLevel = Math.min(n, EDU_LEVELS.length);
    state.eduRowIdx = n;
  }

  initPanels();
  syncEduButton();
  bindButtons();
  Address.init();
});

function initPanels() {
  for (let i = 1; i <= TOTAL_STEPS; i++) {
    const p = document.getElementById(`panel-${i}`);
    if (p) p.style.display = i === 1 ? 'block' : 'none';
  }
  document.getElementById('wzFill').style.width = '0%';
}

function bindButtons() {
  document.getElementById('addChildBtn')?.addEventListener('click', addChildRow);
  document.getElementById('addEduBtn')?.addEventListener('click', addEduRow);
  document.getElementById('addGovIdBtn')?.addEventListener('click', addGovIdRow);
  document.querySelector('[name="civil_status"]')?.addEventListener('change', syncSpouseFields);

  // Re-enable ALL disabled inputs before submit so the browser includes them in the POST.
  // Disabled address fields (street, subdivision, house_number, zip_code) would otherwise
  // be silently omitted, causing the controller to receive empty address data.
  document.getElementById('wizardForm')?.addEventListener('submit', () => {
    document.querySelectorAll('#wizardForm input:disabled, #wizardForm select:disabled').forEach(el => {
      el.disabled = false;
    });
  });
}

// ─────────────────────────────────────────────────────────────
//  WIZARD NAVIGATION
// ─────────────────────────────────────────────────────────────

function changeStep(dir) {
  if (dir === 1 && !validateStep(state.currentStep)) return;
  const next = state.currentStep + dir;
  if (next < 1 || next > TOTAL_STEPS) return;
  goStep(next);
}

function goStep(step) {
  // Leaving review → restore panels 1-4 back to wizardPanelHost
  if (state.currentStep === TOTAL_STEPS && step !== TOTAL_STEPS) {
    restorePanelsFromReview();
  }

  // Hide all panels
  for (let i = 1; i <= TOTAL_STEPS; i++) {
    const p = document.getElementById(`panel-${i}`);
    if (p) {
      p.classList.remove('active');
      p.style.display = 'none';
    }
  }

  // Update step indicators
  document.querySelectorAll('.wz-step').forEach(el => {
    const s = parseInt(el.dataset.step);
    el.classList.remove('active', 'completed');
    if (s < step) el.classList.add('completed');
    else if (s === step) el.classList.add('active');
  });

  // Show target panel
  state.currentStep = step;
  const target = document.getElementById(`panel-${step}`);
  if (target) {
    target.classList.add('active');
    target.style.display = 'block';
  }

  // Progress bar & footer buttons
  document.getElementById('wzFill').style.width = `${((step - 1) / (TOTAL_STEPS - 1)) * 100}%`;
  document.getElementById('prevBtn').style.display = step === 1 ? 'none' : 'inline-flex';
  document.getElementById('nextBtn').style.display = step === TOTAL_STEPS ? 'none' : 'inline-flex';
  document.getElementById('submitBtn').style.display = step === TOTAL_STEPS ? 'inline-flex' : 'none';

  // Spouse sync on step 3
  if (step === 3) syncSpouseFields();

  // Entering review → mount real panels into scroll container
  if (step === TOTAL_STEPS) {
    mountPanelsIntoReview();
    syncSpouseFields();
  }

  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ─────────────────────────────────────────────────────────────
//  REVIEW — move / restore real panels
//  Physically moves panel-1 … panel-4 into #reviewScroll so
//  the user sees their actual filled values with no cloning.
//  Restores them to #wizardPanelHost when navigating back.
// ─────────────────────────────────────────────────────────────

function mountPanelsIntoReview() {
  const scroll = document.getElementById('reviewScroll');
  const host = document.getElementById('wizardPanelHost');
  if (!scroll || !host) return;

  scroll.innerHTML = '';

  for (let i = 1; i <= TOTAL_STEPS - 1; i++) {
    const panel = document.getElementById(`panel-${i}`);
    if (!panel) continue;

    // Sticky coloured section header
    const lbl = REVIEW_SECTION_LABELS[i - 1];
    const header = document.createElement('div');
    header.className = 'review-section-header';
    header.innerHTML = `<i class="ri ${lbl.icon} me-2"></i>${lbl.text}`;
    scroll.appendChild(header);

    // Move the real panel in, make it visible
    panel.style.display = 'block';
    panel.style.paddingTop = '0';
    scroll.appendChild(panel);

    // Divider between sections
    if (i < TOTAL_STEPS - 1) {
      const hr = document.createElement('hr');
      hr.className = 'review-divider';
      scroll.appendChild(hr);
    }
  }

  scroll.scrollTop = 0;
}

function restorePanelsFromReview() {
  const host = document.getElementById('wizardPanelHost');
  if (!host) return;

  for (let i = 1; i <= TOTAL_STEPS - 1; i++) {
    const panel = document.getElementById(`panel-${i}`);
    if (!panel) continue;
    panel.style.display = 'none';
    panel.style.paddingTop = '';
    host.appendChild(panel);
  }
}

// ─────────────────────────────────────────────────────────────
//  VALIDATION
// ─────────────────────────────────────────────────────────────

function validateStep(step) {
  const panel = document.getElementById(`panel-${step}`);
  if (!panel) return true;

  // Only validate fields that are visible and not disabled
  const fields = [...panel.querySelectorAll('[required]')].filter(f => !f.disabled);
  let valid = true;

  fields.forEach(field => {
    field.classList.remove('is-invalid');
    const empty = field.tagName === 'SELECT' ? field.value === '' || field.value === null : !field.value.trim();
    if (empty) {
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
//  SPOUSE FIELDS
// ─────────────────────────────────────────────────────────────

function syncSpouseFields() {
  const status = document.querySelector('[name="civil_status"]')?.value?.toLowerCase() || '';
  const noSpouse = NO_SPOUSE_STATUSES.includes(status);

  ['spouse_name', 'spouse_occupation', 'spouse_employer', 'spouse_business_address'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    if (noSpouse) {
      el.value = '';
      el.disabled = true;
      el.classList.remove('is-invalid');
    } else {
      el.disabled = false;
    }
  });

  const notice = document.getElementById('spouseNotice');
  if (notice) {
    notice.style.setProperty('display', noSpouse ? 'flex' : 'none', 'important');
  }
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
  const used = new Set(
    [...document.querySelectorAll('#educationContainer .edu-row')].map(r => parseInt(r.dataset.level))
  );
  const found = EDU_LEVELS.findIndex(l => !used.has(l.id));
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
        <input type="text" name="children[${idx}][child_name]" class="form-control"
          placeholder="Full Name">
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
//  COPY ADDRESS  ("same as permanent" checkbox)
// ─────────────────────────────────────────────────────────────

function copyAddress(checkbox) {
  if (checkbox.checked) {
    Address.copyPermToCurr();
  } else {
    Address.resetBlock('curr');
  }
}

// ─────────────────────────────────────────────────────────────
//  ADDRESS MODULE — PSGC Cascading + Strict Unlock Chain
//
//  Order per block:  Region → Province → City → Barangay
//                    → Street / Subdivision / House No. / ZIP
//
//  Each field unlocks only after the one above is chosen.
//  Changing a parent re-locks all children.
// ─────────────────────────────────────────────────────────────

const Address = (() => {
  'use strict';

  const BASE = '/admin/psgc';
  const cache = {}; // in-memory fetch cache
  const ts = {}; // TomSelect instances keyed by element id

  // ── fetch with cache ────────────────────────────────────────
  async function apiFetch(url) {
    if (cache[url]) return cache[url];
    const res = await fetch(url);
    if (!res.ok) throw new Error(`PSGC ${url} → ${res.status}`);
    cache[url] = await res.json();
    return cache[url];
  }

  // ── TomSelect helpers ───────────────────────────────────────
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

  function lockSelect(id, placeholder) {
    const inst = ts[id];
    if (!inst) return;
    inst.clear(true);
    inst.clearOptions();
    inst.addOption({ value: '', text: placeholder });
    inst.setValue('', true);
    inst.disable();
  }

  // ── plain text input helpers ────────────────────────────────
  function lockInput(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.value = '';
    el.disabled = true;
    el.readOnly = false;
    el.classList.remove('is-invalid');
  }

  function unlockInput(id) {
    const el = document.getElementById(id);
    if (el) el.disabled = false;
  }

  function lockFreeText(prefix) {
    ['street', 'subdivision', 'house_number', 'zip_code'].forEach(f => lockInput(`${prefix}_${f}`));
  }

  function unlockFreeText(prefix) {
    ['street', 'subdivision', 'house_number', 'zip_code'].forEach(f => unlockInput(`${prefix}_${f}`));
  }

  // ── hidden input helpers ────────────────────────────────────
  function setHidden(id, value) {
    const el = document.getElementById(id);
    if (el) el.value = value ?? '';
  }

  function getLabel(id, code) {
    if (!code || !ts[id]) return '';
    return ts[id].getItem(code)?.textContent?.trim() || '';
  }

  // ── wire one address block ──────────────────────────────────
  function wireBlock(prefix) {
    const id = {
      region: `${prefix}_region`,
      province: `${prefix}_province`,
      city: `${prefix}_city`,
      barangay: `${prefix}_barangay`
    };

    makeTs(id.region);
    makeTs(id.province);
    makeTs(id.city);
    makeTs(id.barangay);

    ts[id.province]?.disable();
    ts[id.city]?.disable();
    ts[id.barangay]?.disable();
    lockFreeText(prefix);

    // Load regions
    apiFetch(`${BASE}/regions`)
      .then(data => {
        populate(id.region, data, '— Select Region —');
        const saved = document.getElementById(`${prefix}_region_code`)?.value;
        if (saved) {
          ts[id.region]?.setValue(saved, true);
          setHidden(`${prefix}_region_name`, getLabel(id.region, saved));
          loadProvinces(prefix, id, saved);
        }
      })
      .catch(() => showError(id.region));

    // Region → Province
    ts[id.region]?.on('change', code => {
      lockSelect(id.province, '— Select Province —');
      lockSelect(id.city, '— Select City / Municipality —');
      lockSelect(id.barangay, '— Select Barangay —');
      lockFreeText(prefix);
      setHidden(`${prefix}_region_code`, code);
      setHidden(`${prefix}_region_name`, getLabel(id.region, code));
      setHidden(`${prefix}_province_code`, '');
      setHidden(`${prefix}_province_name`, '');
      setHidden(`${prefix}_city_code`, '');
      setHidden(`${prefix}_city_name`, '');
      setHidden(`${prefix}_barangay_name`, '');
      if (code) loadProvinces(prefix, id, code);
    });

    // Province → City
    ts[id.province]?.on('change', code => {
      lockSelect(id.city, '— Select City / Municipality —');
      lockSelect(id.barangay, '— Select Barangay —');
      lockFreeText(prefix);
      setHidden(`${prefix}_province_code`, code);
      setHidden(`${prefix}_province_name`, getLabel(id.province, code));
      setHidden(`${prefix}_city_code`, '');
      setHidden(`${prefix}_city_name`, '');
      setHidden(`${prefix}_barangay_name`, '');
      if (code) loadCities(prefix, id, code);
    });

    // City → Barangay
    ts[id.city]?.on('change', code => {
      lockSelect(id.barangay, '— Select Barangay —');
      lockFreeText(prefix);
      setHidden(`${prefix}_city_code`, code);
      setHidden(`${prefix}_city_name`, getLabel(id.city, code));
      setHidden(`${prefix}_barangay_name`, '');
      if (code) loadBarangays(prefix, id, code);
    });

    // Barangay → unlock free text
    ts[id.barangay]?.on('change', code => {
      setHidden(`${prefix}_barangay_name`, getLabel(id.barangay, code));
      if (code) unlockFreeText(prefix);
      else lockFreeText(prefix);
    });
  }

  // ── cascade fetch helpers ───────────────────────────────────
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
      .catch(() => showError(id.province));
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
      .catch(() => showError(id.city));
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
            unlockFreeText(prefix);
          }
        }
      })
      .catch(() => showError(id.barangay));
  }

  function showError(selectId) {
    const wrap = document.getElementById(selectId)?.closest('[class*="col-"]');
    if (wrap && !wrap.querySelector('.psgc-error')) {
      wrap.insertAdjacentHTML(
        'beforeend',
        '<small class="text-danger psgc-error">Failed to load. Check connection.</small>'
      );
    }
  }

  // ── public: copy permanent → current ───────────────────────
  function copyPermToCurr() {
    ['region', 'province', 'city', 'barangay'].forEach(level => {
      const src = ts[`perm_${level}`];
      const dst = ts[`curr_${level}`];
      if (!src || !dst) return;
      const val = src.getValue();
      dst.clear(true);
      dst.clearOptions();
      Object.values(src.options || {}).forEach(o => dst.addOption({ value: o.value, text: o.text }));
      dst.enable();
      dst.setValue(val, true);
      dst.disable();
      setHidden(`curr_${level}_code`, document.getElementById(`perm_${level}_code`)?.value);
      setHidden(`curr_${level}_name`, document.getElementById(`perm_${level}_name`)?.value);
    });

    ['street', 'subdivision', 'house_number', 'zip_code'].forEach(f => {
      const src = document.querySelector(`[name="permanent[${f}]"]`);
      const dst = document.getElementById(`curr_${f}`);
      if (!src || !dst) return;
      dst.value = src.value;
      dst.disabled = true;
      dst.readOnly = true;
    });

    if (!document.getElementById('perm_barangay_name')?.value) {
      lockFreeText('curr');
    }
  }

  // ── public: reset a block ───────────────────────────────────
  function resetBlock(prefix) {
    lockSelect(`${prefix}_province`, '— Select Province —');
    lockSelect(`${prefix}_city`, '— Select City / Municipality —');
    lockSelect(`${prefix}_barangay`, '— Select Barangay —');
    lockFreeText(prefix);

    ['region', 'province', 'city'].forEach(l => {
      setHidden(`${prefix}_${l}_code`, '');
      setHidden(`${prefix}_${l}_name`, '');
    });
    setHidden(`${prefix}_barangay_name`, '');

    apiFetch(`${BASE}/regions`)
      .then(data => populate(`${prefix}_region`, data, '— Select Region —'))
      .catch(() => {});

    ts[`${prefix}_region`]?.enable();
    ts[`${prefix}_region`]?.setValue('', true);
    setHidden(`${prefix}_region_code`, '');
    setHidden(`${prefix}_region_name`, '');

    ['street', 'subdivision', 'house_number', 'zip_code'].forEach(f => {
      const el = document.getElementById(`${prefix}_${f}`);
      if (el) {
        el.value = '';
        el.disabled = true;
        el.readOnly = false;
      }
    });
  }

  // ── public init ─────────────────────────────────────────────
  function init() {
    wireBlock('perm');
    wireBlock('curr');
  }

  return { init, copyPermToCurr, resetBlock };
})();
