'use strict';

const SERVICE_ENDPOINT = 'api/services.php';

const SERVICE_ICON_MAP = {
  Wartung: '🛢️',
  Prüfungen: '📋',
  Verschleißteile: '🧩',
  Reinigung: '✨',
  Reifen: '🔄',
  Fahrwerk: '🛠️',
};

function formatEuro(value) {
  const number = Number(value);
  return new Intl.NumberFormat('de-AT', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number.isFinite(number) ? number : 0);
}

function getServiceIcon(category) {
  return SERVICE_ICON_MAP[category] || '🔧';
}

function renderServiceCard(service, featured = false) {
  const priceLabel = formatEuro(service.price);
  const durationLabel = `${Number(service.duration_minutes) || 0} Min.`;
  const description = service.description || 'Leistung wird aus der Datenbank geladen.';
  const actionLabel = featured ? 'Details' : 'Anfragen';
  const actionHref = featured ? 'leistungen.html' : 'mailto:office@carfixfast.at?subject=' + encodeURIComponent(`Anfrage zu ${service.name}`);

  return `
    <article class="service-card" role="article" aria-label="${escapeHtml(service.name)}">
      <div class="service-card-icon" aria-hidden="true">${getServiceIcon(service.category)}</div>
      <div class="service-card-meta">
        <span class="badge badge-neutral">⏱ ${escapeHtml(durationLabel)}</span>
        ${featured ? '<span class="badge badge-accent">★ Beliebt</span>' : `<span class="badge badge-primary">${escapeHtml(service.category)}</span>`}
      </div>
      <h3>${escapeHtml(service.name)}</h3>
      <p>${escapeHtml(description)}</p>
      <div class="service-card-footer">
        <span class="service-price">${escapeHtml(priceLabel)}</span>
        <a href="${actionHref}" class="btn btn-outline btn-sm">${escapeHtml(actionLabel)}</a>
      </div>
    </article>
  `;
}

function renderServiceGrid(container, services, featured = false) {
  if (!container) return;

  if (services.length === 0) {
    container.innerHTML = `
      <div class="empty-state" role="status">
        <div class="empty-state-icon" aria-hidden="true">🧾</div>
        <h3>Keine Leistungen gefunden</h3>
        <p>Im System sind aktuell keine Leistungen hinterlegt.</p>
      </div>
    `;
    return;
  }

  const visibleServices = featured ? services.slice(0, 3) : services;
  container.innerHTML = visibleServices.map((service) => renderServiceCard(service, featured)).join('');
}

async function loadServices() {
  const response = await fetch(SERVICE_ENDPOINT, {
    headers: { Accept: 'application/json' },
  });

  if (!response.ok) {
    throw new Error('Leistungen konnten nicht geladen werden.');
  }

  const services = await response.json();
  if (!Array.isArray(services)) {
    throw new Error('Ungültige Leistungsdaten.');
  }

  return services;
}

async function initServicesPage() {
  const featuredGrid = document.getElementById('featuredServicesGrid');
  const serviceGrid = document.getElementById('servicesGrid');

  if (!featuredGrid && !serviceGrid) return;

  try {
    const services = await loadServices();
    renderServiceGrid(featuredGrid, services, true);
    renderServiceGrid(serviceGrid, services, false);
  } catch (error) {
    if (featuredGrid) {
      featuredGrid.innerHTML = `<div class="empty-state" role="status"><div class="empty-state-icon" aria-hidden="true">⚠️</div><h3>${escapeHtml(error.message)}</h3></div>`;
    }
    if (serviceGrid) {
      serviceGrid.innerHTML = `<div class="empty-state" role="status"><div class="empty-state-icon" aria-hidden="true">⚠️</div><h3>${escapeHtml(error.message)}</h3></div>`;
    }
  }
}

document.addEventListener('DOMContentLoaded', initServicesPage);
