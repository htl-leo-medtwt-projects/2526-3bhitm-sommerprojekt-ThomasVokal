'use strict';

let toastContainer = null;
let toastTimeouts = new Map();

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
  if (!toast || !toast.parentNode) return;
  
  // Clear any pending timeout for this toast
  if (toast._timeoutId) {
    clearTimeout(toast._timeoutId);
    toast._timeoutId = null;
  }
  
  toast.classList.add('is-leaving');
  
  // Remove from DOM after animation
  const removeHandler = () => {
    if (toast.parentNode) {
      toast.remove();
    }
    toast.removeEventListener('animationend', removeHandler);
  };
  
  toast.addEventListener('animationend', removeHandler, { once: true });
  
  // Fallback: remove after 500ms if animationend doesn't fire
  setTimeout(() => {
    if (toast.parentNode) {
      toast.remove();
    }
  }, 500);
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
    <span class="toast-message">${escapeHtml(message)}</span>
    <button class="toast-close" aria-label="Schließen">✕</button>
  `;

  // Add to container
  const container = getToastContainer();
  container.appendChild(toast);

  // Remove function
  const removeToastFn = () => {
    removeToast(toast);
  };

  // Auto-remove after duration
  const timeoutId = setTimeout(removeToastFn, duration);
  toast._timeoutId = timeoutId;

  // Click on toast closes it (except on close button)
  toast.addEventListener('click', (e) => {
    if (e.target.classList.contains('toast-close')) return;
    removeToastFn();
  });

  // Close button
  const closeBtn = toast.querySelector('.toast-close');
  if (closeBtn) {
    closeBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      removeToastFn();
    });
  }

  // Pause auto-remove on hover
  toast.addEventListener('mouseenter', () => {
    if (toast._timeoutId) {
      clearTimeout(toast._timeoutId);
      toast._timeoutId = null;
    }
  });

  toast.addEventListener('mouseleave', () => {
    if (!toast._timeoutId && toast.parentNode) {
      toast._timeoutId = setTimeout(removeToastFn, 1500);
    }
  });

  return toast;
}

function getFooterHTML() {
  return `
    <footer class="footer" role="contentinfo">
      <div class="container">
        <div class="footer-grid">
          <div>
            <div class="footer-logo">Car<span class="logo-fast">Fix</span>Fast</div>
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
              <li><a href="mailto:office@carfixfast.at">office@carfixfast.at</a></li>
              <li>Werkstattweg 12, 4060 Leonding</li>
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
          <span>© 2026 CarFixFast</span>
          <div class="footer-badges">
            <span class="footer-badge">TÜV</span>
            <span class="footer-badge">§57a</span>
          </div>
        </div>
      </div>
    </footer>
  `;
}

// ============================================================
// Burger Menu Toggle
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
  const burger = document.getElementById('burgerMenu');
  const mobileMenu = document.getElementById('mobileMenu');
  
  if (burger && mobileMenu) {
    burger.addEventListener('click', function(e) {
      e.stopPropagation();
      const isOpen = mobileMenu.classList.toggle('is-open');
      burger.classList.toggle('is-open');
      burger.setAttribute('aria-expanded', isOpen);
      burger.setAttribute('aria-label', isOpen ? 'Menü schließen' : 'Menü öffnen');
    });

    const mobileLinks = mobileMenu.querySelectorAll('.nav-link, .btn');
    mobileLinks.forEach(link => {
      link.addEventListener('click', function() {
        mobileMenu.classList.remove('is-open');
        burger.classList.remove('is-open');
        burger.setAttribute('aria-expanded', 'false');
        burger.setAttribute('aria-label', 'Menü öffnen');
      });
    });

    document.addEventListener('click', function(e) {
      if (!e.target.closest('.nav')) {
        mobileMenu.classList.remove('is-open');
        burger.classList.remove('is-open');
        burger.setAttribute('aria-expanded', 'false');
        burger.setAttribute('aria-label', 'Menü öffnen');
      }
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && mobileMenu.classList.contains('is-open')) {
        mobileMenu.classList.remove('is-open');
        burger.classList.remove('is-open');
        burger.setAttribute('aria-expanded', 'false');
        burger.setAttribute('aria-label', 'Menü öffnen');
        burger.focus();
      }
    });
  }
});

// ============================================================
// Aktive Navigation automatisch setzen
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
  const currentPath = window.location.pathname.split('/').pop() || 'index.html';
  const navLinks = document.querySelectorAll('.nav-link');
  
  navLinks.forEach(link => {
    const href = link.getAttribute('href');
    if (href === currentPath) {
      link.classList.add('active');
      link.setAttribute('aria-current', 'page');
    } else {
      link.classList.remove('active');
      link.removeAttribute('aria-current');
    }
  });
});