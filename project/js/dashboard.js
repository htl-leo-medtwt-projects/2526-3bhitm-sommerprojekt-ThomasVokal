'use strict';

const INITIAL_PROFILE = window.PHP_SESSION_PROFILE || null;
const INITIAL_HISTORY = Array.isArray(window.DASHBOARD_HISTORY)
  ? window.DASHBOARD_HISTORY
  : (Array.isArray(window.DEFAULT_HISTORY) ? window.DEFAULT_HISTORY : []);
const INITIAL_APPOINTMENT = window.DASHBOARD_APPOINTMENT || null;
const NEEDS_VEHICLE_SETUP = Boolean(window.NEEDS_VEHICLE_SETUP);

const state = {
  profile: null,
  modalLocked: false,
  serviceModalOpen: false,
  history: INITIAL_HISTORY,
  appointment: INITIAL_APPOINTMENT,
};

function loadProfileFromSession() {
  if (!INITIAL_PROFILE) return null;
  if (typeof INITIAL_PROFILE !== 'object') return null;
  if (!INITIAL_PROFILE.licensePlate || !INITIAL_PROFILE.make || !INITIAL_PROFILE.model) return null;
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
    throw new Error('Daten konnten nicht gespeichert werden.');
  }

  let result = null;
  try {
    result = await response.json();
  } catch (_) {
    throw new Error('Serverantwort ist ungueltig.');
  }

  if (!result.ok) {
    throw new Error('Speicherung fehlgeschlagen.');
  }
}

async function saveServiceEntry(entry) {
  const response = await fetch('save_service_history.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(entry),
  });

  let result = null;
  try {
    result = await response.json();
  } catch (_) {
    throw new Error('Serverantwort ist ungueltig.');
  }

  if (!response.ok || !result || !result.ok) {
    throw new Error(result && result.error ? result.error : 'Eintrag konnte nicht gespeichert werden.');
  }

  return result.entry;
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
    make: profile.make || 'Noch kein Fahrzeug',
    model: profile.model || 'hinterlegt',
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

function buildFallbackProfile() {
  const storedProfile = INITIAL_PROFILE && typeof INITIAL_PROFILE === 'object' ? INITIAL_PROFILE : {};

  return {
    firstName: storedProfile.firstName || 'Gäste',
    licensePlate: storedProfile.licensePlate || '—',
    make: storedProfile.make || '',
    model: storedProfile.model || '',
    year: storedProfile.year || '',
    mileage: Number.isFinite(Number(storedProfile.mileage)) ? Number(storedProfile.mileage) : 0,
    engine: storedProfile.engine || '',
    color: storedProfile.color || '',
    vin: storedProfile.vin || '',
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
      <span class="vin-label">Fahrgestellnummer (VIN)</span>
      <span class="vin-value">${escapeHtml(vehicle.vin)}</span>
    </div>

    <div class="vehicle-card-footer">
      <span class="badge badge-warning">⏰ Nächste Inspektion: ${escapeHtml(vehicle.nextInspection)}</span>
      <span class="badge badge-primary">🛢️ Nächster Ölwechsel bei ${vehicle.nextOilChange.atKm.toLocaleString('de-AT')} km</span>
    </div>
  `;
}

function renderAppointmentCard(appointment) {
  const el = document.getElementById('appointmentCard');
  if (!el) return;

  if (!appointment) {
    el.innerHTML = `
      <div class="appointment-header">
        <div class="appointment-info">
          <h4>Kein Termin hinterlegt</h4>
          <p>Aktuell ist kein Werkstatttermin im System eingetragen.</p>
        </div>
        <span class="badge badge-neutral">Kein Termin</span>
      </div>

      <div class="appointment-card-body">
        <div class="appointment-card-hint">Tipp</div>
        <div class="appointment-card-title">Sie können jederzeit einen Termin anfragen.</div>
        <div class="appointment-note-card">
          <div class="appointment-note-label">Hinweis</div>
          <p class="appointment-note-text">Sobald ein Termin angelegt ist, erscheint er hier automatisch.</p>
        </div>
      </div>
    `;

    return;
  }

  const statusClass = appointment.status === 'Bestätigt' ? 'badge-primary' : 'badge-neutral';

  el.innerHTML = `
    <div class="appointment-header">
      <div class="appointment-info">
        <h4>${escapeHtml(appointment.title)}</h4>
        <p>📅 ${escapeHtml(appointment.date)} · 🕒 ${escapeHtml(appointment.time)}</p>
      </div>
      <span class="badge ${statusClass}">${escapeHtml(appointment.status)}</span>
    </div>

    <div class="appointment-card-body">
      <div class="appointment-card-hint">Hinweis</div>
      <div class="appointment-card-title">Nächster Werkstatttermin ist gespeichert</div>
      <div class="appointment-note-card">
        <div class="appointment-note-label">Notiz</div>
        <p class="appointment-note-text">${escapeHtml(appointment.note || 'Keine Notiz hinterlegt.')}</p>
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
    <button class="btn btn-outline-white btn-sm oil-alert-action">📅 Termin anfragen</button>
  `;
}

function renderHistoryStats(vehicle) {
  const el = document.getElementById('historyStats');
  if (!el) return;

  let totalCost = 0;
  for (const entry of state.history) {
    totalCost += entry.cost;
  }

  const latestKm = vehicle.mileage;
  const firstKm = state.history.length > 0 ? state.history[state.history.length - 1].mileage : vehicle.mileage;
  let drivenKm = latestKm - firstKm;
  if (drivenKm < 0) {
    drivenKm = 0;
  }

  const lastVisit = state.history.length > 0 ? state.history[0].date : 'Noch kein Eintrag';

  const stats = [
    { icon: '🔧', label: 'Werkstattbesuche', value: state.history.length },
    { icon: '💶', label: 'Gesamt investiert', value: `€ ${Math.round(totalCost)}` },
    { icon: '📍', label: 'Gefahrene km (gesamt)', value: `${drivenKm.toLocaleString('de-AT')} km` },
    { icon: '📅', label: 'Letzter Besuch', value: lastVisit },
  ];

  let html = '';
  for (const stat of stats) {
    html += `
      <div class="card card-body dashboard-stat-card">
        <div class="dashboard-stat-icon" aria-hidden="true">${stat.icon}</div>
        <div class="dashboard-stat-value">${escapeHtml(String(stat.value))}</div>
        <div class="dashboard-stat-label">${escapeHtml(stat.label)}</div>
      </div>
    `;
  }

  el.innerHTML = html;
}

function renderTimeline() {
  const el = document.getElementById('historyTimeline');
  if (!el) return;

  if (state.history.length === 0) {
    el.innerHTML = `
      <div class="empty-state" role="status">
        <div class="empty-state-icon" aria-hidden="true">🧾</div>
        <h3>Noch keine Werkstatteinträge</h3>
        <p>Sobald Servicearbeiten gespeichert werden, erscheinen sie hier automatisch.</p>
      </div>
    `;
    return;
  }

  let html = '';

  for (const entry of state.history) {
    const dotColor = entry.dotColor ? entry.dotColor : 'primary';
    html += `
      <div class="timeline-item" role="listitem" aria-label="${escapeHtml(entry.service)}, ${escapeHtml(entry.date)}">
        <div class="timeline-dot dot-${dotColor}" aria-hidden="true"></div>
        <div class="timeline-card timeline-card-content">
          <div class="timeline-service">${escapeHtml(entry.service)}</div>
          <div class="timeline-date">📅 ${escapeHtml(entry.date)} · 📍 ${entry.mileage.toLocaleString('de-AT')} km</div>
          <div class="timeline-meta">
            Mechaniker: ${escapeHtml(entry.mechanic)} · Kosten: € ${toDePrice(entry.cost)}
          </div>
          ${entry.notes ? `<div class="timeline-note">${escapeHtml(entry.notes)}</div>` : ''}
        </div>
      </div>
    `;
  }

  el.innerHTML = html;
}

function renderWelcome(profile, vehicle) {
  const title = document.getElementById('welcomeTitle');
  const subtitle = document.getElementById('welcomeSubtitle');
  const statusBadge = document.getElementById('welcomeStatusBadge');
  const summaryNote = document.getElementById('welcomeSummaryNote');

  if (title) {
    title.textContent = `Hallo, ${profile.firstName} 👋`;
  }

  if (subtitle) {
    subtitle.textContent = 'Hier finden Sie Termine, Services und den Werkstattpass auf einen Blick.';
  }

  if (statusBadge) {
    const vehicleLabel = state.history.length > 0 ? 'Werkstattpass aktiv' : 'Fahrzeugdaten geladen';
    statusBadge.textContent = `● ${vehicleLabel}`;
  }

  if (summaryNote) {
    const serviceCount = state.history.length;
    const appointmentText = state.appointment
      ? `Nächster Termin: ${state.appointment.date}`
      : 'Aktuell kein Termin hinterlegt';
    summaryNote.textContent = `${serviceCount} Serviceeinträge · ${appointmentText}`;
  }
}

function renderDashboard(profile) {
  const vehicle = getVehicleData(profile);
  renderWelcome(profile, vehicle);
  renderVehicleCard(vehicle);
  renderAppointmentCard(state.appointment);
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
  form.vin.value = safeProfile.vin || '';
}

function openProfileModal(lock = false) {
  const overlay = document.getElementById('vehicleProfileModal');
  const closeBtn = document.getElementById('vehicleProfileCloseBtn');
  const cancelBtn = document.getElementById('vehicleProfileCancelBtn');

  if (!overlay) return;

  state.modalLocked = lock;
  overlay.classList.add('is-open');
  overlay.setAttribute('aria-hidden', 'false');
  document.body.classList.add('modal-open');

  if (closeBtn) closeBtn.classList.toggle('is-hidden', lock);
  if (cancelBtn) cancelBtn.classList.toggle('is-hidden', lock);

  const firstInput = document.getElementById('profileFirstName');
  if (firstInput) firstInput.focus();
}

function closeProfileModal() {
  const overlay = document.getElementById('vehicleProfileModal');
  if (!overlay) return;
  if (state.modalLocked && !state.profile) return;

  overlay.classList.remove('is-open');
  overlay.setAttribute('aria-hidden', 'true');
  document.body.classList.remove('modal-open');
}

function setServiceEntryDefaults() {
  const form = document.getElementById('serviceEntryForm');
  if (!form) return;

  const serviceDateInput = form.querySelector('[name="serviceDate"]');
  const serviceNameInput = form.querySelector('[name="serviceName"]');
  const mileageInput = form.querySelector('[name="mileage"]');
  const costInput = form.querySelector('[name="cost"]');
  const mechanicInput = form.querySelector('[name="mechanic"]');
  const notesInput = form.querySelector('[name="notes"]');

  if (!serviceDateInput || !serviceNameInput || !mileageInput || !costInput || !mechanicInput || !notesInput) {
    return;
  }

  const today = new Date();
  const month = String(today.getMonth() + 1).padStart(2, '0');
  const day = String(today.getDate()).padStart(2, '0');
  const isoDate = `${today.getFullYear()}-${month}-${day}`;

  serviceDateInput.value = isoDate;
  serviceNameInput.value = '';
  mileageInput.value = state.profile && Number.isFinite(Number(state.profile.mileage))
    ? String(Math.max(0, Math.round(Number(state.profile.mileage))))
    : '';
  costInput.value = '';
  mechanicInput.value = '';
  notesInput.value = '';
}

function handleOpenServiceEntry() {
  if (!state.profile) {
    state.profile = loadProfileFromSession();
  }

  if (!state.profile) {
    setFormValues(null);
    openProfileModal(true);
    showToast('Bitte zuerst Fahrzeugdaten speichern.', 'warning', 3200);
    return;
  }

  setServiceEntryDefaults();
  openServiceEntryModal();
}

function openServiceEntryModal() {
  const overlay = document.getElementById('serviceEntryModal');
  if (!overlay) return;

  state.serviceModalOpen = true;
  overlay.classList.add('is-open');
  overlay.setAttribute('aria-hidden', 'false');
  document.body.classList.add('modal-open');

  const firstInput = document.getElementById('serviceDate');
  if (firstInput) firstInput.focus();
}

function closeServiceEntryModal() {
  const overlay = document.getElementById('serviceEntryModal');
  if (!overlay) return;

  state.serviceModalOpen = false;
  overlay.classList.remove('is-open');
  overlay.setAttribute('aria-hidden', 'true');
  document.body.classList.remove('modal-open');
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
    vin: String(data.get('vin') || '').trim().toUpperCase(),
  };

  const missingRequired = !profile.firstName || !profile.licensePlate || !profile.make || !profile.model;
  if (missingRequired) throw new Error('Bitte fuellen Sie alle Pflichtfelder aus.');

  const invalidYear = !Number.isInteger(profile.year) || profile.year < 1980 || profile.year > 2035;
  if (invalidYear) throw new Error('Bitte geben Sie ein gueltiges Baujahr ein (1980 bis 2035).');

  const invalidMileage = !Number.isFinite(profile.mileage) || profile.mileage < 0;
  if (invalidMileage) throw new Error('Bitte geben Sie einen gueltigen Kilometerstand ein.');

  return profile;
}

function readServiceEntryFromForm() {
  const form = document.getElementById('serviceEntryForm');
  if (!form) {
    throw new Error('Formular wurde nicht gefunden.');
  }

  const data = new FormData(form);

  const entry = {
    serviceDate: String(data.get('serviceDate') || '').trim(),
    serviceName: String(data.get('serviceName') || '').trim(),
    mileage: Number(data.get('mileage')),
    cost: Number(data.get('cost')),
    mechanic: String(data.get('mechanic') || '').trim(),
    notes: String(data.get('notes') || '').trim(),
  };

  if (!entry.serviceDate || !entry.serviceName) {
    throw new Error('Bitte Datum und Servicebezeichnung eingeben.');
  }

  if (!Number.isInteger(entry.mileage) || entry.mileage < 0) {
    throw new Error('Bitte einen gueltigen Kilometerstand eingeben.');
  }

  if (!Number.isFinite(entry.cost) || entry.cost < 0) {
    throw new Error('Bitte gueltige Kosten eingeben.');
  }

  return entry;
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
      showToast('Fahrzeugdaten wurden gespeichert.', 'success', 3200);
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

function initServiceEntryModal() {
  const form = document.getElementById('serviceEntryForm');
  const overlay = document.getElementById('serviceEntryModal');
  const openBtn = document.getElementById('addServiceEntryBtn');
  const closeBtn = document.getElementById('serviceEntryCloseBtn');
  const cancelBtn = document.getElementById('serviceEntryCancelBtn');

  if (!form || !overlay) return;

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    try {
      const payload = readServiceEntryFromForm();
      const savedEntry = await saveServiceEntry(payload);
      state.history.unshift(savedEntry);

      if (state.profile && savedEntry && Number.isFinite(Number(savedEntry.mileage))) {
        const nextMileage = Math.max(Number(state.profile.mileage) || 0, Number(savedEntry.mileage));
        state.profile.mileage = nextMileage;
      }

      if (state.profile) {
        renderDashboard(state.profile);
      }

      closeServiceEntryModal();
      showToast('Serviceeintrag wurde gespeichert.', 'success', 3200);
    } catch (error) {
      showToast(error.message, 'warning', 3600);
    }
  });

  if (openBtn) {
    openBtn.addEventListener('click', handleOpenServiceEntry);
  } else {
    document.addEventListener('click', (event) => {
      const target = event.target;
      if (!(target instanceof Element)) return;
      const trigger = target.closest('#addServiceEntryBtn');
      if (!trigger) return;
      handleOpenServiceEntry();
    });
  }

  if (closeBtn) closeBtn.addEventListener('click', closeServiceEntryModal);
  if (cancelBtn) cancelBtn.addEventListener('click', closeServiceEntryModal);

  overlay.addEventListener('click', (event) => {
    if (event.target === overlay) {
      closeServiceEntryModal();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && state.serviceModalOpen) {
      closeServiceEntryModal();
    }
  });
}

function initActions() {
  const exportBtn = document.getElementById('exportPassBtn');
  if (exportBtn) {
    exportBtn.addEventListener('click', () => {
      showToast('Werkstattpass wird als PDF vorbereitet…', 'info', 3500);
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
  initServiceEntryModal();
  initActions();

  state.profile = loadProfileFromSession() || buildFallbackProfile();

  if (NEEDS_VEHICLE_SETUP) {
    setFormValues(state.profile);
  }

  if (state.profile) {
    renderDashboard(state.profile);
  } else {
    setFormValues(null);
  }
}

document.addEventListener('DOMContentLoaded', init);
