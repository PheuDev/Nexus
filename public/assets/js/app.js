/* NEXUS — APP.JS
   Utilitaires partagés : AJAX, toasts, horloge, navigation mobile,
   micro-interactions, animations, toggle thème.*/

const NEXUS = {

    csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',

    async post(url, data = {}) {
        const body = new URLSearchParams({ ...data, csrf_token: NEXUS.csrfToken });
        const res  = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type':     'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body,
        });
        return res.json();
    },

    async get(url) {
        const res = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        return res.json();
    },

    toast(message, type = 'success') {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        const icons = { success: '✓', error: '✕', info: '→' };
        const el = document.createElement('div');
        el.className = `toast ${type}`;
        el.innerHTML = `<span style="margin-right:8px;font-weight:700;">${icons[type] ?? '·'}</span>${message}`;
        container.appendChild(el);
        setTimeout(() => {
            el.style.cssText += 'opacity:0;transform:translateX(20px);transition:opacity .3s ease,transform .3s ease;';
            setTimeout(() => el.remove(), 320);
        }, 3200);
    },

    /**
     Modale de confirmation stylisée — remplace window.confirm().
     Retourne une Promise<boolean>.
     */
    confirm(message, title = 'Confirmer', icon = '⚠', danger = false) {
        return new Promise((resolve) => {
            let modal = document.getElementById('nexus-confirm-modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'nexus-confirm-modal';
                modal.innerHTML = `
                    <div class="nexus-confirm-box">
                        <div class="nexus-confirm-icon" id="nc-icon"></div>
                        <div class="nexus-confirm-title" id="nc-title"></div>
                        <div class="nexus-confirm-message" id="nc-msg"></div>
                        <div class="nexus-confirm-actions">
                            <button id="nc-cancel" class="btn">Annuler</button>
                            <button id="nc-ok" class="btn btn-primary">Confirmer</button>
                        </div>
                    </div>`;
                document.body.appendChild(modal);
            }
            modal.querySelector('#nc-icon').textContent  = icon;
            modal.querySelector('#nc-title').textContent = title;
            modal.querySelector('#nc-msg').textContent   = message;
            const okBtn = modal.querySelector('#nc-ok');
            okBtn.className = danger ? 'btn btn-danger' : 'btn btn-primary';
            okBtn.textContent = danger ? 'Supprimer' : 'Confirmer';

            modal.classList.add('open');

            const close = (result) => {
                modal.classList.remove('open');
                resolve(result);
            };
            okBtn.onclick               = () => close(true);
            modal.querySelector('#nc-cancel').onclick = () => close(false);
            modal.onclick = (e) => { if (e.target === modal) close(false); };
        });
    },

    /** Compatibilité ascendante — les appels à confirmAction restent valides. */
    confirmAction(message) {
        return window.confirm(message);
    },

    animateProgress() {
        document.querySelectorAll('.progress-fill[data-width]').forEach(el => {
            requestAnimationFrame(() => {
                setTimeout(() => { el.style.width = el.dataset.width + '%'; }, 80);
            });
        });
    },

    /**
     * Toggle thème dark/light. Sauvegarde la préférence en base via AJAX, applique immédiatement.
    */
    async toggleTheme() {
        const html        = document.documentElement;
        const current     = html.getAttribute('data-theme') || 'dark';
        const next        = current === 'dark' ? 'light' : 'dark';

        // Application immédiate (pas d'attente serveur)
        html.setAttribute('data-theme', next);
        NEXUS._updateThemeToggleIcon(next);

        // Sauvegarde en session + DB
        const res = await NEXUS.post('index.php?page=settings&action=update', {
            theme:                 next,
            currency:              document.querySelector('meta[name="user-currency"]')?.content || 'EUR',
            notifications_enabled: '1',
        });

        if (!res.success) {
            // Rollback si erreur
            html.setAttribute('data-theme', current);
            NEXUS._updateThemeToggleIcon(current);
        }
    },

    _updateThemeToggleIcon(theme) {
        const btn = document.getElementById('nexus-theme-toggle');
        if (!btn) return;
        const isDark = theme === 'dark';
        // Soleil en mode dark (cliquer = passer en light), Lune en mode light (cliquer = passer en dark)
        btn.innerHTML = isDark
            ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" width="15" height="15" aria-hidden="true"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>`
            : `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" width="15" height="15" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>`;
        btn.title = isDark ? 'Passer en mode Light' : 'Passer en mode Dark';
    },
};

/* RECHERCHE GLOBALE */
function nexusInitSearch() {
    const input   = document.getElementById('nexus-search-input');
    const results = document.getElementById('nexus-search-results');
    if (!input || !results) return;

    const icons = {
        wallet:   '<path d="M20 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 14a1 1 0 1 0 2 0 1 1 0 0 0-2 0"/>',
        target:   '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/>',
        calendar: '<rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/>',
    };

    let debounceTimer;

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const q = input.value.trim();
        if (q.length < 2) { results.style.display = 'none'; return; }

        debounceTimer = setTimeout(async () => {
            const res = await NEXUS.get(`index.php?page=search&q=${encodeURIComponent(q)}`);
            if (!res.success) return;

            if (res.results.length === 0) {
                results.innerHTML = `<div class="search-empty">Aucun résultat pour « ${q} »</div>`;
            } else {
                results.innerHTML = res.results.map(r => `
                    <a href="${r.url}" class="search-result-item" role="option">
                        <div class="search-result-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                                ${icons[r.icon] ?? ''}
                            </svg>
                        </div>
                        <div style="min-width:0;">
                            <div class="search-result-title">${r.title}</div>
                            <div class="search-result-sub">${r.sub}${r.date ? ' · ' + r.date : ''}</div>
                        </div>
                    </a>`).join('');
            }
            results.style.display = 'block';
            input.setAttribute('aria-expanded', 'true');
        }, 280);
    });

    // Ferme les résultats en cliquant ailleurs
    document.addEventListener('click', (e) => {
        if (!e.target.closest('#nexus-search-wrap')) {
            results.style.display = 'none';
            input.setAttribute('aria-expanded', 'false');
        }
    });

    // Navigation clavier dans les résultats
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { results.style.display = 'none'; input.blur(); }
    });
}

/* NAVIGATION MOBILE */

function nexusInitMobileNav() {
    const sidebar = document.getElementById('nexus-sidebar');
    const overlay = document.getElementById('nexus-overlay');
    const toggle  = document.getElementById('nexus-hamburger');
    if (!sidebar || !overlay || !toggle) return;

    const open  = () => { sidebar.classList.add('open');    overlay.classList.add('open');    toggle.setAttribute('aria-expanded', 'true');  };
    const close = () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); toggle.setAttribute('aria-expanded', 'false'); };

    toggle.addEventListener('click', open);
    overlay.addEventListener('click', close);
    sidebar.querySelectorAll('a').forEach(l => l.addEventListener('click', close));
    window.addEventListener('resize', () => { if (window.innerWidth > 1100) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
}

/* FOCUS TRAP MODALES */
function nexusInitModalFocus() {
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.nexus-modal-backdrop.open').forEach(m => m.classList.remove('open'));
        }
        if (e.key !== 'Tab') return;
        const openModal = document.querySelector('.nexus-modal-backdrop.open');
        if (!openModal) return;
        const focusables = openModal.querySelectorAll('button,input,select,textarea,a[href]');
        if (!focusables.length) return;
        const first = focusables[0], last = focusables[focusables.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });
}

/* ANIMATIONS D'ENTRÉE TABLEAU / LISTE */
/* TOGGLE THÈME — init icône au chargement */
function nexusInitThemeToggle() {
    const theme = document.documentElement.getAttribute('data-theme') || 'dark';
    NEXUS._updateThemeToggleIcon(theme);
    const btn = document.getElementById('nexus-theme-toggle');
    if (btn) btn.addEventListener('click', () => NEXUS.toggleTheme());
}

/* INITIALISATION */
document.addEventListener('DOMContentLoaded', () => {
    nexusInitMobileNav();
    nexusInitModalFocus();
    nexusInitThemeToggle();
    nexusInitSearch();
    NEXUS.animateProgress();
});
