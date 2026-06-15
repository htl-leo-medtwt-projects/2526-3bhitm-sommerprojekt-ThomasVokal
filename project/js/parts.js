'use strict';

const PARTS_ENDPOINT = 'api/parts.php';

const CATEGORY_META = {
  alle: { label: 'Alle', icon: '🧩' },
  bremsen: { label: 'Bremsen', icon: '🛞' },
  filter: { label: 'Filter', icon: '🌬️' },
  oele: { label: 'Öle & Flüssigkeiten', icon: '🛢️' },
  zuendung: { label: 'Zündung & Elektrik', icon: '⚡' },
  reifen: { label: 'Reifen', icon: '🛞' },
  fahrwerk: { label: 'Fahrwerk', icon: '🧰' },
  auspuff: { label: 'Auspuff', icon: '💨' },
  sonstiges: { label: 'Sonstiges', icon: '🔧' },
};

const state = {
  parts: [],
  activeCategory: 'alle',
  search: '',
  sort: 'default',
};

const CATEGORY_IMAGE_FALLBACKS = {
  bremsen: 'assets/parts/brake.svg',
  filter: 'assets/parts/filter.svg',
  oele: 'assets/parts/oil.svg',
  zuendung: 'assets/parts/spark.svg',
  reifen: 'assets/parts/tire.svg',
  fahrwerk: 'assets/parts/suspension.svg',
  auspuff: 'assets/parts/default.svg',
  sonstiges: 'assets/parts/default.svg',
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

function normalizeText(value) {
  return String(value ?? '').toLowerCase();
}

function detectCategory(part) {
  const text = normalizeText([part.part_number, part.name, part.brand, part.description].join(' '));

  if (text.includes('brems') || text.includes('brake')) return 'bremsen';
  if (text.includes('filter')) return 'filter';
  if (text.includes('öl') || text.includes('oel') || text.includes('oil') || text.includes('fluid') || text.includes('coolant') || text.includes('kühl')) return 'oele';
  if (text.includes('spark') || text.includes('zünd') || text.includes('kerze') || text.includes('ignition') || text.includes('battery') || text.includes('lamp') || text.includes('bulb') || text.includes('wiper')) return 'zuendung';
  if (text.includes('reifen') || text.includes('tire')) return 'reifen';
  if (text.includes('dämpfer') || text.includes('dampfer') || text.includes('shock') || text.includes('absorber') || text.includes('belt') || text.includes('riemen') || text.includes('suspension')) return 'fahrwerk';
  if (text.includes('auspuff') || text.includes('exhaust') || text.includes('muffler')) return 'auspuff';

  return 'sonstiges';
}

function getPartIcon(category, part) {
  const text = normalizeText(part.name);

  if (category === 'bremsen') return '🛑';
  if (category === 'filter') return '🌬️';
  if (category === 'oele') return '🛢️';
  if (category === 'zuendung') return '⚡';
  if (category === 'reifen') return '🛞';
  if (category === 'fahrwerk') return '🧰';
  if (category === 'auspuff') return '💨';
  if (text.includes('battery')) return '🔋';

  return '🔧';
}

function getCategoryLabel(category) {
  const meta = CATEGORY_META[category] || CATEGORY_META.sonstiges;
  return `${meta.icon} ${meta.label}`;
}

function getImagePath(category, part) {
  const path = String(part.image_path ?? '').trim();
  if (path) return path;

  return CATEGORY_IMAGE_FALLBACKS[category] || CATEGORY_IMAGE_FALLBACKS.sonstiges;
}

function renderPartMedia(category, part) {
  const imagePath = getImagePath(category, part);
  const fallbackIcon = getPartIcon(category, part);

  if (imagePath) {
    return `
      <div class="part-card-img">
        <img class="part-card-image" src="${escapeHtml(imagePath)}" alt="${escapeHtml(part.name)}" loading="lazy" data-fallback-icon="${escapeHtml(fallbackIcon)}" />
      </div>
    `;
  }

  return `
    <div class="part-card-img part-card-img-placeholder" aria-hidden="true">
      <div class="part-card-image-fallback">
        <span class="part-card-image-icon">${fallbackIcon}</span>
        <span class="part-card-image-label">Keine Vorschau</span>
      </div>
    </div>
  `;
}

function wirePartImageFallbacks() {
  document.querySelectorAll('.part-card-image').forEach((image) => {
    if (image.dataset.fallbackBound === 'true') return;

    image.dataset.fallbackBound = 'true';
    image.addEventListener('error', () => {
      const media = image.closest('.part-card-img');
      if (!media) return;

      const fallbackIcon = image.dataset.fallbackIcon || '🔧';
      media.innerHTML = `
        <div class="part-card-image-fallback" aria-hidden="true">
          <span class="part-card-image-icon">${escapeHtml(fallbackIcon)}</span>
          <span class="part-card-image-label">Vorschau nicht verfügbar</span>
        </div>
      `;
    }, { once: true });
  });
}

function buildFilterButtons(parts) {
  const filterBar = document.getElementById('partsFilter');
  if (!filterBar) return;

  const counts = parts.reduce((accumulator, part) => {
    const category = detectCategory(part);
    accumulator[category] = (accumulator[category] || 0) + 1;
    return accumulator;
  }, {});

  const categories = ['alle', ...Object.keys(CATEGORY_META).filter((category) => category !== 'alle')];

  filterBar.innerHTML = categories.map((category) => {
    const count = category === 'alle' ? parts.length : (counts[category] || 0);
    const meta = CATEGORY_META[category];
    const activeClass = category === state.activeCategory ? ' active' : '';
    return `<button type="button" class="filter-pill${activeClass}" data-category="${category}">${escapeHtml(`${meta.icon} ${meta.label} (${count})`)}</button>`;
  }).join('');
}

function applyFilters(parts) {
  const search = normalizeText(state.search).trim();

  let filtered = parts.filter((part) => {
    const category = detectCategory(part);
    if (state.activeCategory !== 'alle' && category !== state.activeCategory) {
      return false;
    }

    if (!search) return true;

    const haystack = normalizeText([
      part.part_number,
      part.name,
      part.brand,
      part.description,
    ].join(' '));

    return haystack.includes(search);
  });

  if (state.sort === 'price-asc') {
    filtered = [...filtered].sort((left, right) => Number(left.price) - Number(right.price));
  } else if (state.sort === 'price-desc') {
    filtered = [...filtered].sort((left, right) => Number(right.price) - Number(left.price));
  } else if (state.sort === 'name-az') {
    filtered = [...filtered].sort((left, right) => left.name.localeCompare(right.name, 'de', { sensitivity: 'base' }));
  } else if (state.sort === 'new-first') {
    filtered = [...filtered].sort((left, right) => {
      const rightDate = new Date(right.created_at || 0).getTime();
      const leftDate = new Date(left.created_at || 0).getTime();
      return rightDate - leftDate;
    });
  }

  return filtered;
}

function renderPartsGrid(parts) {
  const grid = document.getElementById('partsGrid');
  const emptyState = document.getElementById('partsEmpty');
  const resultsCount = document.getElementById('partsCount');

  if (!grid || !emptyState || !resultsCount) return;

  if (parts.length === 0) {
    grid.innerHTML = '';
    emptyState.style.display = 'block';
    resultsCount.innerHTML = '<strong>0</strong> Artikel gefunden';
    return;
  }

  emptyState.style.display = 'none';
  resultsCount.innerHTML = `<strong>${parts.length}</strong> Artikel gefunden`;

  grid.innerHTML = parts.map((part) => {
    const category = detectCategory(part);
    const categoryLabel = getCategoryLabel(category);
    const stockQuantity = Number(part.stock_quantity) || 0;
    const stockBadgeClass = stockQuantity > 10 ? 'badge-success' : (stockQuantity > 0 ? 'badge-warning' : 'badge-neutral');
    const stockLabel = stockQuantity > 0 ? `${stockQuantity} auf Lager` : 'Nicht auf Lager';
    const actionHref = `mailto:office@carfixfast.at?subject=${encodeURIComponent(`Anfrage zu ${part.name}`)}`;

    return `
      <article class="part-card" role="listitem" aria-label="${escapeHtml(part.name)}">
        ${renderPartMedia(category, part)}
        <div class="part-card-body">
          <span class="badge badge-neutral">${escapeHtml(categoryLabel)}</span>
          <div class="part-name">${escapeHtml(part.name)}</div>
          <div class="part-brand">${escapeHtml(part.brand || 'Ohne Marke')}</div>
          <div class="part-number">${escapeHtml(part.part_number)}</div>
          <p class="part-description">${escapeHtml(part.description || 'Beschreibung wird aus der Datenbank geladen.')}</p>
          <div class="part-compat">${escapeHtml(stockLabel)}</div>
          <div class="part-price-row">
            <div class="part-price">${escapeHtml(formatEuro(part.price))}</div>
            <a class="reserve-btn" href="${actionHref}">Anfragen</a>
          </div>
          <span class="badge ${stockBadgeClass}">${escapeHtml(stockLabel)}</span>
        </div>
      </article>
    `;
  }).join('');

  wirePartImageFallbacks();
}

function updateFilterBar(parts) {
  buildFilterButtons(parts);

  const filterBar = document.getElementById('partsFilter');
  if (!filterBar) return;

  filterBar.querySelectorAll('.filter-pill').forEach((button) => {
    button.addEventListener('click', () => {
      state.activeCategory = button.dataset.category || 'alle';
      updateFilterBar(state.parts);
      renderPartsGrid(applyFilters(state.parts));
    });
  });
}

async function loadParts() {
  const response = await fetch(PARTS_ENDPOINT, {
    headers: { Accept: 'application/json' },
  });

  if (!response.ok) {
    throw new Error('Ersatzteile konnten nicht geladen werden.');
  }

  const parts = await response.json();
  if (!Array.isArray(parts)) {
    throw new Error('Ungültige Ersatzteildaten.');
  }

  return parts;
}

function wireControls() {
  const searchInput = document.getElementById('partsSearch');
  const sortSelect = document.getElementById('partsSort');
  const resetButton = document.getElementById('partsResetBtn');

  if (searchInput) {
    searchInput.addEventListener('input', () => {
      state.search = searchInput.value;
      renderPartsGrid(applyFilters(state.parts));
    });
  }

  if (sortSelect) {
    sortSelect.addEventListener('change', () => {
      state.sort = sortSelect.value;
      renderPartsGrid(applyFilters(state.parts));
    });
  }

  if (resetButton) {
    resetButton.addEventListener('click', () => {
      state.activeCategory = 'alle';
      state.search = '';
      state.sort = 'default';

      if (searchInput) searchInput.value = '';
      if (sortSelect) sortSelect.value = 'default';

      updateFilterBar(state.parts);
      renderPartsGrid(applyFilters(state.parts));
    });
  }
}

async function initPartsPage() {
  const grid = document.getElementById('partsGrid');
  if (!grid) return;

  wireControls();

  try {
    state.parts = await loadParts();
    updateFilterBar(state.parts);
    renderPartsGrid(applyFilters(state.parts));
  } catch (error) {
    grid.innerHTML = '';
    const emptyState = document.getElementById('partsEmpty');
    const resultsCount = document.getElementById('partsCount');

    if (emptyState) {
      emptyState.style.display = 'block';
      emptyState.querySelector('h3').textContent = error.message;
      const text = emptyState.querySelector('p');
      if (text) text.textContent = 'Bitte versuchen Sie es später noch einmal.';
    }

    if (resultsCount) {
      resultsCount.innerHTML = '<strong>0</strong> Artikel gefunden';
    }
  }
}

document.addEventListener('DOMContentLoaded', initPartsPage);
