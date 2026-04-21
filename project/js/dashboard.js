'use strict';

const INITIAL_PROFILE = window.PHP_SESSION_PROFILE || null;

const state = {
  profile: null,
  modalLocked: false,
};

const STATIC_APPOINTMENT = {
  title: 'Nächster Werkstatttermin',
  date: 'Dienstag, 28. April 2026',
  time: '10:30 Uhr',
  note: 'Bitte Fahrzeugpapiere mitbringen. Das ist ein fixer Demo-Text.',
};

function loadProfileFromSession() {
  if (!INITIAL_PROFILE) return null;
  if (typeof INITIAL_PROFILE !== 'object') return null;
  return INITIAL_PROFILE;
}

async function saveProfileToSession(profile) {
  const response = await fetch('save_profile.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(profile),
  });

  if (!response.ok) {
    throw new Error('Daten konnten nicht in der PHP-Session gespeichert werden.');
  }

  let result = null;
  try {
    result = await response.json();
  } catch (_) {
    throw new Error('Serverantwort ist ungueltig.');
  }

  if (!result.ok) {
    throw new Error('Session-Speicherung fehlgeschlagen.');
  }
}

function getVehicleData(profile) {
  let mileage = Number(profile.mileage);
  if (!Number.isFinite(mileage) || mileage < 0) {
    mileage = 0;
  }

  const nextStepSize = 10000;
  const kilometersForRounding = mileage + 1;
  const safeKilometers = Math.max(kilometersForRounding, 1);
  const roundedSteps = Math.ceil(safeKilometers / nextStepSize);
  const nextOilAtKm = roundedSteps * nextStepSize;

  let remainingKm = nextOilAtKm - mileage;
  if (remainingKm < 0) {
    remainingKm = 0;
  }

  const parsedYear = Number(profile.year);
  const year = Number.isFinite(parsedYear) ? parsedYear : 0;

  const engine = profile.engine ? profile.engine : 'Nicht angegeben';
  const color = profile.color ? profile.color : 'Nicht angegeben';
  const tireSize = profile.tireSize ? profile.tireSize : 'Nicht angegeben';
  const vin = profile.vin ? profile.vin : 'Nicht angegeben';

  return {
    make: profile.make,
    model: profile.model,
    engine,
    year,
    licensePlate: profile.licensePlate,
    mileage,
    color,
    tireSize,
    vin,
    nextInspection: 'In den nächsten 12 Monaten',
    nextOilChange: {
      atKm: nextOilAtKm,
      remainingKm,
      latestBy: 'in ca. 6 Monaten',
    },
  };
}

function toDePrice(value) {
  const numericValue = Number(value);
  const formatted = numericValue.toFixed(2);
  return formatted.replace('.', ',');
}

function renderVehicleCard(vehicle) {
  const el = document.getElementById('vehicleCard');
  if (!el) return;

  el.innerHTML = `
    <div class="vehicle-header">
      <div class="vehicle-icon" aria-hidden="true">🚗</div>
      <div>
        <div class="vehicle-title">${escapeHtml(vehicle.make)} ${escapeHtml(vehicle.model)}</div>
        <div class="vehicle-subtitle">${escapeHtml(vehicle.engine)} · ${vehicle.year} · ${escapeHtml(vehicle.color)}</div>
      </div>
    </div>

    <div class="vehicle-specs">
      <div class="spec-item">
        <label>Kennzeichen</label>
        <span>${escapeHtml(vehicle.licensePlate)}</span>
      </div>
      <div class="spec-item">
        <label>Kilometerstand</label>
        <span>${vehicle.mileage.toLocaleString('de-AT')} km</span>
      </div>
      <div class="spec-item">
        <label>Baujahr</label>
        <span>${vehicle.year}</span>
      </div>
      <div class="spec-item">
        <label>Reifengröße</label>
        <span>${escapeHtml(vehicle.tireSize)}</span>
      </div>
    </div>

    <div class="vehicle-vin">
      <span class="vin-label">Fahrgestellnummer (FIN)</span>
      <span class="vin-value">${escapeHtml(vehicle.vin)}</span>
    </div>

    <div style="padding: var(--space-4) var(--space-6); border-top: 1px solid var(--color-border); display:flex; gap: var(--space-3); flex-wrap:wrap;">
      <span class="badge badge-warning">⏰ Nächste Inspektion: ${escapeHtml(vehicle.nextInspection)}</span>
      <span class="badge badge-primary">🛢️ Nächster Ölwechsel bei ${vehicle.nextOilChange.atKm.toLocaleString('de-AT')} km</span>
    </div>
  `;
}

function renderAppointmentCard() {
  const el = document.getElementById('appointmentCard');
  if (!el) return;

  el.innerHTML = `
    <div class="appointment-header">
      <div class="appointment-info">
        <h4>${escapeHtml(STATIC_APPOINTMENT.title)}</h4>
        <p>📅 ${escapeHtml(STATIC_APPOINTMENT.date)} · 🕒 ${escapeHtml(STATIC_APPOINTMENT.time)}</p>
      </div>
      <span class="badge badge-primary">Fixer Termin</span>
    </div>

    <div style="padding: var(--space-4) var(--space-6); border-top: 1px solid var(--color-border); background: var(--color-surface-2);">
      <div style="font-size: var(--font-size-xs); color: var(--color-text-muted); margin-bottom: var(--space-1);">Hinweis</div>
      <div style="font-size: var(--font-size-sm); font-weight: 600; color: var(--color-text);">Hier werden aktuelle Termininfos angezeigt</div>
      <div style="margin-top: var(--space-3); padding: var(--space-3); background: white; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
        <div style="font-size: var(--font-size-xs); font-weight:600; color: var(--color-text-muted); margin-bottom: var(--space-1);">Notiz</div>
        <p style="font-size: var(--font-size-xs); color: var(--color-text); font-style:italic;">${escapeHtml(STATIC_APPOINTMENT.note)}</p>
      </div>
    </div>
  `;
}

function renderOilAlert(vehicle) {
  const oil = vehicle.nextOilChange;
  const el = document.getElementById('oilAlert');
  if (!el) return;

  el.innerHTML = `
    <div class="oil-alert-icon" aria-hidden="true">🛢️</div>
    <div>
      <h4>Nächster Ölwechsel empfohlen</h4>
      <p>
        In ca. <strong>${oil.remainingKm.toLocaleString('de-AT')} km</strong>
        (spätestens ${escapeHtml(oil.latestBy)}) bei
        <strong>${oil.atKm.toLocaleString('de-AT')} km</strong>.
      </p>
    </div>
    <button class="btn btn-outline-white btn-sm" style="margin-left:auto; flex-shrink:0;">📅 Termin anfragen</button>
  `;
}

function renderHistoryStats(vehicle) {
  const el = document.getElementById('historyStats');
  if (!el) return;

  let totalCost = 0;
  for (const entry of DEFAULT_HISTORY) {
    totalCost += entry.cost;
  }

  const latestKm = vehicle.mileage;
  const firstKm = DEFAULT_HISTORY[DEFAULT_HISTORY.length - 1].mileage;
  let drivenKm = latestKm - firstKm;
  if (drivenKm < 0) {
    drivenKm = 0;
  }

  const stats = [
    { icon: '🔧', label: 'Werkstattbesuche', value: DEFAULT_HISTORY.length },
    { icon: '💶', label: 'Gesamt investiert', value: `€ ${Math.round(totalCost)}` },
    { icon: '📍', label: 'Gefahrene km (gesamt)', value: `${drivenKm.toLocaleString('de-AT')} km` },
    { icon: '📅', label: 'Letzter Besuch', value: DEFAULT_HISTORY[0].date },
  ];

  let html = '';
  for (const stat of stats) {
    html += `
      <div class="card card-body" style="text-align:center;">
        <div style="font-size: 1.8rem; margin-bottom: var(--space-2);" aria-hidden="true">${stat.icon}</div>
        <div style="font-size: var(--font-size-lg); font-weight: 800; color: var(--color-primary); margin-bottom: 2px;">${escapeHtml(String(stat.value))}</div>
        <div style="font-size: var(--font-size-xs); color: var(--color-text-muted); font-weight:600;">${escapeHtml(stat.label)}</div>
      </div>
    `;
  }

  el.innerHTML = html;
}

function renderTimeline() {
  const el = document.getElementById('historyTimeline');
  if (!el) return;

  let html = '';

  for (const entry of DEFAULT_HISTORY) {
    const dotColor = entry.dotColor ? entry.dotColor : 'primary';
    html += `
      <div class="timeline-item" role="listitem" aria-label="${escapeHtml(entry.service)}, ${escapeHtml(entry.date)}">
        <div class="timeline-dot dot-${dotColor}" aria-hidden="true"></div>
        <div class="timeline-card" style="padding: var(--space-4);">
          <div class="timeline-service">${escapeHtml(entry.service)}</div>
          <div class="timeline-date">📅 ${escapeHtml(entry.date)} · 📍 ${entry.mileage.toLocaleString('de-AT')} km</div>
          <div style="margin-top: var(--space-3); font-size: var(--font-size-sm); color: var(--color-text-muted);">
            Mechaniker: ${escapeHtml(entry.mechanic)} · Kosten: € ${toDePrice(entry.cost)}
          </div>
        </div>
      </div>
    `;
  }

  el.innerHTML = html;
}

function renderWelcome(profile, vehicle) {
  const title = document.getElementById('welcomeTitle');
  const subtitle = document.getElementById('welcomeSubtitle');

  if (title) {
    title.textContent = `Hallo, ${profile.firstName} 👋`;
  }

  if (subtitle) {
    subtitle.textContent = `Ihr ${vehicle.make} ${vehicle.model} ist erfasst. Nächster Ölwechsel in ca. ${vehicle.nextOilChange.remainingKm.toLocaleString('de-AT')} km.`;
  }
}

function renderDashboard(profile) {
  const vehicle = getVehicleData(profile);
  renderWelcome(profile, vehicle);
  renderVehicleCard(vehicle);
  renderAppointmentCard();
  renderOilAlert(vehicle);
  renderHistoryStats(vehicle);
  renderTimeline();
}

function setFormValues(profile) {
  const form = document.getElementById('vehicleProfileForm');
  if (!form) return;

  const safeProfile = profile || {};

  form.firstName.value = safeProfile.firstName || '';
  form.licensePlate.value = safeProfile.licensePlate || '';
  form.make.value = safeProfile.make || '';
  form.model.value = safeProfile.model || '';
  form.year.value = safeProfile.year || '';
  form.mileage.value = safeProfile.mileage || '';
  form.engine.value = safeProfile.engine || '';
  form.color.value = safeProfile.color || '';
}

function openProfileModal(lock = false) {
  const overlay = document.getElementById('vehicleProfileModal');
  const closeBtn = document.getElementById('vehicleProfileCloseBtn');
  const cancelBtn = document.getElementById('vehicleProfileCancelBtn');

  if (!overlay) return;

  state.modalLocked = lock;
  overlay.classList.add('is-open');
  overlay.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';

  if (closeBtn) closeBtn.style.display = lock ? 'none' : '';
  if (cancelBtn) cancelBtn.style.display = lock ? 'none' : '';

  const firstInput = document.getElementById('profileFirstName');
  if (firstInput) firstInput.focus();
}

function closeProfileModal() {
  const overlay = document.getElementById('vehicleProfileModal');
  if (!overlay) return;
  if (state.modalLocked && !state.profile) return;

  overlay.classList.remove('is-open');
  overlay.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
}

function readProfileFromForm() {
  const form = document.getElementById('vehicleProfileForm');
  if (!form) {
    throw new Error('Formular wurde nicht gefunden.');
  }

  const data = new FormData(form);

  const profile = {
    firstName: String(data.get('firstName') || '').trim(),
    licensePlate: String(data.get('licensePlate') || '').trim().toUpperCase(),
    make: String(data.get('make') || '').trim(),
    model: String(data.get('model') || '').trim(),
    year: Number(data.get('year')),
    mileage: Number(data.get('mileage')),
    engine: String(data.get('engine') || '').trim(),
    color: String(data.get('color') || '').trim(),
  };

  const missingRequired = !profile.firstName || !profile.licensePlate || !profile.make || !profile.model;
  if (missingRequired) throw new Error('Bitte fuellen Sie alle Pflichtfelder aus.');

  const invalidYear = !Number.isInteger(profile.year) || profile.year < 1980 || profile.year > 2035;
  if (invalidYear) throw new Error('Bitte geben Sie ein gueltiges Baujahr ein (1980 bis 2035).');

  const invalidMileage = !Number.isFinite(profile.mileage) || profile.mileage < 0;
  if (invalidMileage) throw new Error('Bitte geben Sie einen gueltigen Kilometerstand ein.');

  return profile;
}

function initProfileModal() {
  const form = document.getElementById('vehicleProfileForm');
  const overlay = document.getElementById('vehicleProfileModal');
  const closeBtn = document.getElementById('vehicleProfileCloseBtn');
  const cancelBtn = document.getElementById('vehicleProfileCancelBtn');
  const editBtn = document.getElementById('editVehicleBtn');

  if (!form || !overlay) return;

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    try {
      const profile = readProfileFromForm();
      await saveProfileToSession(profile);
      state.profile = profile;
      renderDashboard(profile);
      closeProfileModal();
      showToast('Fahrzeugdaten wurden in der PHP-Session gespeichert.', 'success', 3200);
    } catch (error) {
      showToast(error.message, 'warning', 3600);
    }
  });

  if (closeBtn) closeBtn.addEventListener('click', closeProfileModal);
  if (cancelBtn) cancelBtn.addEventListener('click', closeProfileModal);

  overlay.addEventListener('click', (event) => {
    if (event.target === overlay) {
      closeProfileModal();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeProfileModal();
    }
  });

  if (editBtn) {
    editBtn.addEventListener('click', () => {
      setFormValues(state.profile);
      openProfileModal(false);
    });
  }
}

function initActions() {
  const exportBtn = document.getElementById('exportPassBtn');
  if (exportBtn) {
    exportBtn.addEventListener('click', () => {
      showToast('Werkstattpass wird als PDF vorbereitet… (Demo)', 'info', 3500);
    });
  }
}

function mountFooter() {
  const footer = document.getElementById('footerMount');
  if (footer) footer.innerHTML = getFooterHTML();
}

function init() {
  mountFooter();
  initProfileModal();
  initActions();

  state.profile = loadProfileFromSession();

  if (state.profile) {
    renderDashboard(state.profile);
    return;
  }

  setFormValues(null);
  openProfileModal(true);
}

document.addEventListener('DOMContentLoaded', init);
