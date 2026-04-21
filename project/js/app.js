'use strict';

let toastContainer = null;

function escapeHtml(value) {
  const div = document.createElement('div');
  div.appendChild(document.createTextNode(String(value ?? '')));
  return div.innerHTML;
}

function getToastContainer() {
  if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.className = 'toast-container';
    toastContainer.setAttribute('role', 'region');
    toastContainer.setAttribute('aria-live', 'polite');
    document.body.appendChild(toastContainer);
  }
  return toastContainer;
}

function removeToast(toast) {
  toast.classList.add('is-leaving');
  toast.addEventListener('animationend', () => toast.remove(), { once: true });
}

function showToast(message, type = 'success', duration = 3500) {
  const icons = {
    success: '✅',
    error: '❌',
    warning: '⚠️',
    info: 'ℹ️',
  };

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.setAttribute('role', 'status');
  toast.innerHTML = `
    <span class="toast-icon">${icons[type] || icons.info}</span>
    <span>${escapeHtml(message)}</span>
  `;

  getToastContainer().appendChild(toast);

  const timer = setTimeout(() => removeToast(toast), duration);
  toast.addEventListener('click', () => {
    clearTimeout(timer);
    removeToast(toast);
  });
}

function getFooterHTML() {
  return `
    <footer class="footer" role="contentinfo">
      <div class="container">
        <div class="footer-grid">
          <div>
            <div class="footer-logo">Auto<span class="logo-fast">Fix</span>Fast</div>
            <p class="footer-desc">Kfz-Werkstatt in Leonding.</p>
          </div>
          <div>
            <p class="footer-heading">Links</p>
            <ul class="footer-links">
              <li><a href="index.html">Startseite</a></li>
              <li><a href="leistungen.html">Leistungen</a></li>
              <li><a href="ersatzteile.html">Ersatzteile</a></li>
            </ul>
          </div>
          <div>
            <p class="footer-heading">Kontakt</p>
            <ul class="footer-links">
              <li><a href="tel:+43316123456">+43 316 123 456</a></li>
              <li><a href="mailto:office@autofixfast.at">office@autofixfast.at</a></li>
              <li>Werkstattgasse 12</li>
            </ul>
          </div>
          <div>
            <p class="footer-heading">Öffnungszeiten</p>
            <ul class="footer-links">
              <li>Mo–Do: 07:30 – 18:00 Uhr</li>
              <li>Fr: 07:30 – 17:00 Uhr</li>
              <li>Sa: 08:00 – 13:00 Uhr</li>
            </ul>
          </div>
        </div>

        <div class="footer-bottom">
          <span>© 2026 AutoFixFast</span>
          <div class="footer-badges">
            <span class="footer-badge">TÜV</span>
            <span class="footer-badge">§57a</span>
          </div>
        </div>
      </div>
    </footer>
  `;
}
