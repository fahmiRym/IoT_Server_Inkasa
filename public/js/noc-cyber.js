/*!
 * NOC Cyber UI — helper library (toast/alert/confirm/prompt/loading)
 * Zero dependency. Semua fungsi return Promise.
 * Global: window.NocCyber
 * -----------------------------------------------------------------
 */
(function (w, d) {
    'use strict';

    const CSS_ID = 'noc-cyber-css';
    if (!d.getElementById(CSS_ID)) {
        const style = d.createElement('style');
        style.id = CSS_ID;
        style.textContent = `
        .nc-toast-stack{position:fixed;top:16px;right:16px;display:flex;flex-direction:column;gap:10px;z-index:100000;pointer-events:none;max-width:min(360px,calc(100vw - 32px))}
        .nc-toast{pointer-events:auto;background:rgba(10,15,25,0.92);backdrop-filter:blur(14px);border:1px solid var(--border,rgba(0,243,255,.2));border-left:3px solid var(--cyan,#00f3ff);border-radius:10px;padding:12px 14px;color:var(--text-vibrant,#e6f1f8);font-family:'Rajdhani',system-ui,sans-serif;font-size:.85rem;box-shadow:0 8px 32px rgba(0,0,0,.5),0 0 20px rgba(0,243,255,.08);display:flex;gap:10px;align-items:flex-start;position:relative;overflow:hidden;animation:nc-toast-in .28s cubic-bezier(.2,.9,.3,1.2)}
        .nc-toast.nc-success{border-left-color:var(--green,#0aff00);box-shadow:0 8px 32px rgba(0,0,0,.5),0 0 20px rgba(10,255,0,.12)}
        .nc-toast.nc-error{border-left-color:var(--pink,#ff0055);box-shadow:0 8px 32px rgba(0,0,0,.5),0 0 20px rgba(255,0,85,.12)}
        .nc-toast.nc-warning{border-left-color:var(--yellow,#ffcc00);box-shadow:0 8px 32px rgba(0,0,0,.5),0 0 20px rgba(255,204,0,.12)}
        .nc-toast.nc-info{border-left-color:var(--cyan,#00f3ff)}
        .nc-toast.nc-leaving{animation:nc-toast-out .22s ease-in forwards}
        .nc-toast i.nc-ico{font-size:1rem;margin-top:1px}
        .nc-toast.nc-success i.nc-ico{color:var(--green,#0aff00)}
        .nc-toast.nc-error i.nc-ico{color:var(--pink,#ff0055)}
        .nc-toast.nc-warning i.nc-ico{color:var(--yellow,#ffcc00)}
        .nc-toast.nc-info i.nc-ico{color:var(--cyan,#00f3ff)}
        .nc-toast .nc-msg{flex:1;line-height:1.35;word-wrap:break-word}
        .nc-toast .nc-msg strong{font-family:'Orbitron',sans-serif;font-size:.7rem;letter-spacing:1.5px;text-transform:uppercase;display:block;margin-bottom:2px;color:var(--cyan,#00f3ff)}
        .nc-toast.nc-success .nc-msg strong{color:var(--green,#0aff00)}
        .nc-toast.nc-error .nc-msg strong{color:var(--pink,#ff0055)}
        .nc-toast.nc-warning .nc-msg strong{color:var(--yellow,#ffcc00)}
        .nc-toast .nc-x{background:transparent;border:0;color:var(--text-dim,#708090);cursor:pointer;font-size:.75rem;padding:0 2px;line-height:1;opacity:.7;transition:opacity .15s}
        .nc-toast .nc-x:hover{opacity:1;color:var(--text-vibrant,#e6f1f8)}
        .nc-toast .nc-bar{position:absolute;bottom:0;left:0;height:2px;background:currentColor;opacity:.4;width:100%;transform-origin:left;animation:nc-bar-drain linear forwards}
        @keyframes nc-toast-in{from{opacity:0;transform:translateX(20px) scale(.95)}to{opacity:1;transform:translateX(0) scale(1)}}
        @keyframes nc-toast-out{to{opacity:0;transform:translateX(20px) scale(.95)}}
        @keyframes nc-bar-drain{from{transform:scaleX(1)}to{transform:scaleX(0)}}

        .nc-modal-overlay{position:fixed;inset:0;background:rgba(2,4,8,.75);backdrop-filter:blur(6px);z-index:99998;display:flex;align-items:center;justify-content:center;padding:20px;animation:nc-fade-in .18s ease-out}
        .nc-modal-overlay.nc-leaving{animation:nc-fade-out .16s ease-in forwards}
        .nc-modal{background:rgba(10,15,25,.92);backdrop-filter:blur(18px);border:1px solid var(--border,rgba(0,243,255,.2));border-radius:14px;padding:26px 24px 22px;max-width:min(440px,100%);width:100%;color:var(--text-vibrant,#e6f1f8);font-family:'Rajdhani',sans-serif;position:relative;box-shadow:0 20px 60px rgba(0,0,0,.65),0 0 30px rgba(0,243,255,.12);animation:nc-pop-in .22s cubic-bezier(.2,.9,.3,1.2)}
        .nc-modal.nc-danger{border-color:rgba(255,0,85,.35);box-shadow:0 20px 60px rgba(0,0,0,.65),0 0 30px rgba(255,0,85,.18)}
        .nc-modal.nc-warning{border-color:rgba(255,204,0,.35);box-shadow:0 20px 60px rgba(0,0,0,.65),0 0 30px rgba(255,204,0,.15)}
        .nc-modal.nc-success{border-color:rgba(10,255,0,.35);box-shadow:0 20px 60px rgba(0,0,0,.65),0 0 30px rgba(10,255,0,.15)}
        .nc-modal::before{content:'';position:absolute;inset:0;border-radius:14px;padding:1px;background:linear-gradient(135deg,var(--cyan,#00f3ff),transparent 60%,var(--pink,#ff0055));-webkit-mask:linear-gradient(#fff 0 0) content-box,linear-gradient(#fff 0 0);-webkit-mask-composite:xor;mask-composite:exclude;pointer-events:none;opacity:.4}
        .nc-modal.nc-danger::before{background:linear-gradient(135deg,var(--pink,#ff0055),transparent 60%,var(--yellow,#ffcc00))}
        .nc-modal.nc-warning::before{background:linear-gradient(135deg,var(--yellow,#ffcc00),transparent 60%,var(--pink,#ff0055))}
        .nc-modal.nc-success::before{background:linear-gradient(135deg,var(--green,#0aff00),transparent 60%,var(--cyan,#00f3ff))}
        .nc-modal-icon{width:64px;height:64px;margin:0 auto 14px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid var(--cyan,#00f3ff);background:radial-gradient(circle,rgba(0,243,255,.18),transparent 70%);box-shadow:0 0 24px rgba(0,243,255,.35)}
        .nc-modal.nc-danger .nc-modal-icon{border-color:var(--pink,#ff0055);background:radial-gradient(circle,rgba(255,0,85,.2),transparent 70%);box-shadow:0 0 24px rgba(255,0,85,.4);animation:nc-shake .5s cubic-bezier(.36,.07,.19,.97) infinite}
        .nc-modal.nc-warning .nc-modal-icon{border-color:var(--yellow,#ffcc00);background:radial-gradient(circle,rgba(255,204,0,.2),transparent 70%);box-shadow:0 0 24px rgba(255,204,0,.4)}
        .nc-modal.nc-success .nc-modal-icon{border-color:var(--green,#0aff00);background:radial-gradient(circle,rgba(10,255,0,.2),transparent 70%);box-shadow:0 0 24px rgba(10,255,0,.4)}
        .nc-modal-icon i{font-size:1.7rem;color:var(--cyan,#00f3ff);filter:drop-shadow(0 0 6px currentColor)}
        .nc-modal.nc-danger .nc-modal-icon i{color:var(--pink,#ff0055)}
        .nc-modal.nc-warning .nc-modal-icon i{color:var(--yellow,#ffcc00)}
        .nc-modal.nc-success .nc-modal-icon i{color:var(--green,#0aff00)}
        .nc-modal-title{font-family:'Orbitron',sans-serif;font-weight:900;font-size:1rem;letter-spacing:2.5px;text-align:center;text-transform:uppercase;color:var(--cyan,#00f3ff);margin-bottom:8px}
        .nc-modal.nc-danger .nc-modal-title{color:var(--pink,#ff0055)}
        .nc-modal.nc-warning .nc-modal-title{color:var(--yellow,#ffcc00)}
        .nc-modal.nc-success .nc-modal-title{color:var(--green,#0aff00)}
        .nc-modal-body{font-size:.9rem;text-align:center;color:var(--text-dim,#a0aec0);line-height:1.5;margin-bottom:18px;font-family:'Rajdhani',sans-serif}
        .nc-modal-body strong{color:var(--text-vibrant,#e6f1f8)}
        .nc-modal-input{width:100%;padding:11px 13px;background:rgba(0,0,0,.4);border:1px solid var(--border,rgba(0,243,255,.2));border-radius:8px;color:var(--text-vibrant,#e6f1f8);font-family:'JetBrains Mono',monospace;font-size:.9rem;outline:none;margin-bottom:16px;transition:all .15s;box-sizing:border-box}
        .nc-modal-input:focus{border-color:var(--cyan,#00f3ff);box-shadow:0 0 12px rgba(0,243,255,.2);background:rgba(0,243,255,.03)}
        .nc-modal-actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
        .nc-btn{flex:1;min-width:110px;padding:11px 18px;border-radius:8px;cursor:pointer;font-family:'Orbitron',sans-serif;font-weight:700;font-size:.72rem;letter-spacing:2px;text-transform:uppercase;transition:all .18s;border:1px solid transparent;display:inline-flex;align-items:center;justify-content:center;gap:6px}
        .nc-btn.nc-btn-cancel{background:transparent;border-color:var(--border,rgba(0,243,255,.2));color:var(--text-dim,#708090)}
        .nc-btn.nc-btn-cancel:hover{border-color:var(--cyan,#00f3ff);color:var(--cyan,#00f3ff);background:rgba(0,243,255,.05)}
        .nc-btn.nc-btn-confirm{background:linear-gradient(135deg,rgba(0,243,255,.2),rgba(0,243,255,.05));border-color:var(--cyan,#00f3ff);color:var(--cyan,#00f3ff)}
        .nc-btn.nc-btn-confirm:hover{background:rgba(0,243,255,.28);box-shadow:0 0 18px rgba(0,243,255,.4);transform:translateY(-2px)}
        .nc-modal.nc-danger .nc-btn.nc-btn-confirm{background:linear-gradient(135deg,rgba(255,0,85,.22),rgba(255,0,85,.05));border-color:var(--pink,#ff0055);color:var(--pink,#ff0055)}
        .nc-modal.nc-danger .nc-btn.nc-btn-confirm:hover{background:rgba(255,0,85,.3);box-shadow:0 0 18px rgba(255,0,85,.4)}
        .nc-modal.nc-warning .nc-btn.nc-btn-confirm{background:linear-gradient(135deg,rgba(255,204,0,.22),rgba(255,204,0,.05));border-color:var(--yellow,#ffcc00);color:var(--yellow,#ffcc00)}
        .nc-modal.nc-warning .nc-btn.nc-btn-confirm:hover{background:rgba(255,204,0,.3);box-shadow:0 0 18px rgba(255,204,0,.4)}
        .nc-modal.nc-success .nc-btn.nc-btn-confirm{background:linear-gradient(135deg,rgba(10,255,0,.22),rgba(10,255,0,.05));border-color:var(--green,#0aff00);color:var(--green,#0aff00)}
        .nc-modal.nc-success .nc-btn.nc-btn-confirm:hover{background:rgba(10,255,0,.3);box-shadow:0 0 18px rgba(10,255,0,.4)}

        .nc-loading{position:fixed;inset:0;background:rgba(2,4,8,.82);backdrop-filter:blur(4px);z-index:99999;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;animation:nc-fade-in .16s}
        .nc-loading.nc-leaving{animation:nc-fade-out .16s ease-in forwards}
        .nc-loading .nc-spin{width:52px;height:52px;border:3px solid rgba(0,243,255,.15);border-top-color:var(--cyan,#00f3ff);border-radius:50%;animation:nc-spin .9s linear infinite;box-shadow:0 0 24px rgba(0,243,255,.35)}
        .nc-loading .nc-lbl{font-family:'Orbitron',sans-serif;font-size:.75rem;letter-spacing:3px;color:var(--cyan,#00f3ff);text-transform:uppercase;text-shadow:0 0 12px rgba(0,243,255,.5)}
        .nc-loading .nc-sub{font-family:'JetBrains Mono',monospace;font-size:.65rem;color:var(--text-dim,#708090);letter-spacing:1px}
        @keyframes nc-spin{to{transform:rotate(360deg)}}
        @keyframes nc-fade-in{from{opacity:0}to{opacity:1}}
        @keyframes nc-fade-out{to{opacity:0}}
        @keyframes nc-pop-in{from{opacity:0;transform:scale(.9) translateY(6px)}to{opacity:1;transform:scale(1) translateY(0)}}
        @keyframes nc-shake{0%,100%{transform:translate(0,0)}25%{transform:translate(-1px,1px)}75%{transform:translate(1px,-1px)}}

        @media (max-width:520px){
            .nc-toast-stack{top:10px;right:10px;left:10px;max-width:none}
            .nc-modal{padding:22px 18px 18px}
            .nc-modal-title{font-size:.85rem;letter-spacing:1.8px}
            .nc-modal-body{font-size:.82rem}
            .nc-btn{padding:10px 12px;font-size:.68rem;letter-spacing:1.4px;min-width:0}
        }
        @media (prefers-reduced-motion:reduce){
            .nc-toast,.nc-modal,.nc-modal-overlay,.nc-loading{animation:none!important}
            .nc-toast .nc-bar{animation:none;transform:scaleX(0)}
            .nc-modal.nc-danger .nc-modal-icon{animation:none}
        }
        `;
        d.head.appendChild(style);
    }

    let stackEl = null;
    function stack() {
        if (stackEl && d.body.contains(stackEl)) return stackEl;
        stackEl = d.createElement('div');
        stackEl.className = 'nc-toast-stack';
        d.body.appendChild(stackEl);
        return stackEl;
    }

    const ICONS = {
        success: 'fa-solid fa-circle-check',
        error:   'fa-solid fa-circle-xmark',
        warning: 'fa-solid fa-triangle-exclamation',
        info:    'fa-solid fa-circle-info',
    };
    const TITLES = {
        success: 'SUCCESS',
        error:   'ERROR',
        warning: 'WARNING',
        info:    'INFO',
    };

    function escapeHtml(s) {
        return String(s ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function toast(msg, opts) {
        opts = opts || {};
        const type = opts.type || 'info';
        const title = opts.title !== undefined ? opts.title : TITLES[type];
        const duration = opts.duration === 0 ? 0 : (opts.duration || 3800);
        const icon = opts.icon || ICONS[type] || ICONS.info;

        const el = d.createElement('div');
        el.className = 'nc-toast nc-' + type;
        el.innerHTML =
            '<i class="nc-ico ' + icon + '"></i>' +
            '<div class="nc-msg">' + (title ? '<strong>' + escapeHtml(title) + '</strong>' : '') + escapeHtml(msg) + '</div>' +
            '<button class="nc-x" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>' +
            (duration > 0 ? '<div class="nc-bar" style="animation-duration:' + duration + 'ms"></div>' : '');
        stack().appendChild(el);

        const kill = () => {
            if (!el.parentNode) return;
            el.classList.add('nc-leaving');
            setTimeout(() => { el.parentNode && el.parentNode.removeChild(el); }, 260);
        };
        el.querySelector('.nc-x').addEventListener('click', kill);
        if (duration > 0) setTimeout(kill, duration + 60);
        return kill;
    }

    function openModal(opts) {
        opts = opts || {};
        const overlay = d.createElement('div');
        overlay.className = 'nc-modal-overlay';

        const type = opts.type || 'info';
        const icon = opts.icon || ICONS[type] || ICONS.info;
        const title = opts.title !== undefined ? opts.title : TITLES[type];

        const modal = d.createElement('div');
        modal.className = 'nc-modal nc-' + type;
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');

        const html = [
            '<div class="nc-modal-icon"><i class="' + icon + '"></i></div>',
            title ? '<div class="nc-modal-title">' + escapeHtml(title) + '</div>' : '',
            '<div class="nc-modal-body">' + (opts.htmlBody ? opts.message : escapeHtml(opts.message || '')) + '</div>',
        ];
        if (opts.input) {
            html.push('<input type="' + (opts.input.type || 'text') +
                '" class="nc-modal-input" placeholder="' + escapeHtml(opts.input.placeholder || '') +
                '" value="' + escapeHtml(opts.input.value || '') + '">');
        }

        const showCancel = opts.showCancel !== false;
        const confirmText = opts.confirmText || 'OK';
        const cancelText  = opts.cancelText  || 'BATAL';

        html.push('<div class="nc-modal-actions">' +
            (showCancel ? '<button class="nc-btn nc-btn-cancel" data-act="cancel">' + escapeHtml(cancelText) + '</button>' : '') +
            '<button class="nc-btn nc-btn-confirm" data-act="confirm">' + escapeHtml(confirmText) + '</button>' +
        '</div>');

        modal.innerHTML = html.join('');
        overlay.appendChild(modal);
        d.body.appendChild(overlay);

        const input = modal.querySelector('.nc-modal-input');
        if (input) setTimeout(() => input.focus(), 60);
        else {
            const confirmBtn = modal.querySelector('[data-act="confirm"]');
            if (confirmBtn) setTimeout(() => confirmBtn.focus(), 60);
        }

        return new Promise((resolve) => {
            const finish = (val) => {
                overlay.classList.add('nc-leaving');
                setTimeout(() => { overlay.parentNode && overlay.parentNode.removeChild(overlay); }, 180);
                d.removeEventListener('keydown', onKey);
                resolve(val);
            };
            const onKey = (e) => {
                if (e.key === 'Escape' && showCancel) finish(null);
                else if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    finish(input ? input.value : true);
                }
            };
            d.addEventListener('keydown', onKey);
            overlay.addEventListener('click', (e) => { if (e.target === overlay && showCancel) finish(null); });
            modal.querySelector('[data-act="confirm"]').addEventListener('click', () => finish(input ? input.value : true));
            const cbtn = modal.querySelector('[data-act="cancel"]');
            if (cbtn) cbtn.addEventListener('click', () => finish(null));
        });
    }

    let loadingEl = null;
    function loading(label, sub) {
        if (loadingEl && d.body.contains(loadingEl)) {
            loadingEl.querySelector('.nc-lbl').textContent = label || 'PROCESSING';
            loadingEl.querySelector('.nc-sub').textContent = sub || '';
            return () => hideLoading();
        }
        loadingEl = d.createElement('div');
        loadingEl.className = 'nc-loading';
        loadingEl.innerHTML =
            '<div class="nc-spin"></div>' +
            '<div class="nc-lbl">' + escapeHtml(label || 'PROCESSING') + '</div>' +
            '<div class="nc-sub">' + escapeHtml(sub || '') + '</div>';
        d.body.appendChild(loadingEl);
        return () => hideLoading();
    }
    function hideLoading() {
        if (!loadingEl) return;
        loadingEl.classList.add('nc-leaving');
        const el = loadingEl;
        loadingEl = null;
        setTimeout(() => { el.parentNode && el.parentNode.removeChild(el); }, 180);
    }

    // ── Public API ──
    const NocCyber = {
        toast:   (msg, opts) => toast(msg, opts),
        success: (msg, opts) => toast(msg, Object.assign({ type: 'success' }, opts || {})),
        error:   (msg, opts) => toast(msg, Object.assign({ type: 'error' },   opts || {})),
        warning: (msg, opts) => toast(msg, Object.assign({ type: 'warning' }, opts || {})),
        info:    (msg, opts) => toast(msg, Object.assign({ type: 'info' },    opts || {})),

        alert: (msg, opts) => openModal(Object.assign({
            message: msg, showCancel: false, confirmText: 'MENGERTI',
        }, opts || {})),

        confirm: (msg, opts) => openModal(Object.assign({
            message: msg, type: 'warning',
            confirmText: 'YA, LANJUT', cancelText: 'BATAL',
        }, opts || {})).then(v => v !== null),

        danger: (msg, opts) => openModal(Object.assign({
            message: msg, type: 'danger',
            confirmText: 'YA, HAPUS', cancelText: 'BATAL',
        }, opts || {})).then(v => v !== null),

        prompt: (msg, opts) => openModal(Object.assign({
            message: msg, input: { type: 'text' },
            confirmText: 'KIRIM', cancelText: 'BATAL',
        }, opts || {})),

        loading: (label, sub) => loading(label, sub),
        hideLoading,
    };

    // Auto-hook <form data-confirm="..."> untuk kompatibilitas markup lama
    d.addEventListener('submit', function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        const msg = form.getAttribute('data-confirm');
        if (!msg || form.dataset.ncOk === '1') return;
        e.preventDefault();
        const type = form.getAttribute('data-confirm-type') || 'warning';
        const title = form.getAttribute('data-confirm-title') || undefined;
        (type === 'danger' ? NocCyber.danger(msg, { title, htmlBody: true })
                           : NocCyber.confirm(msg, { title, type, htmlBody: true }))
            .then((ok) => {
                if (ok) { form.dataset.ncOk = '1'; form.submit(); }
            });
    }, true);

    w.NocCyber = NocCyber;
})(window, document);
