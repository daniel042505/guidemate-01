(function() {
    var modal = null;

    function getModal() {
        if (modal) return modal;
        modal = document.createElement('div');
        modal.id = 'logoutModal';
        modal.innerHTML = '<div class="logout-modal-overlay">' +
            '<div class="logout-modal-box">' +
            '<p class="logout-modal-msg">You sure want to logout?</p>' +
            '<div class="logout-modal-btns">' +
            '<button type="button" class="logout-modal-btn logout-modal-no">NO</button>' +
            '<button type="button" class="logout-modal-btn logout-modal-yes">YES</button>' +
            '</div></div></div>';
        modal.style.cssText = 'position:fixed;inset:0;z-index:99999;display:none;align-items:center;justify-content:center;';
        document.body.appendChild(modal);

        var overlay = modal.querySelector('.logout-modal-overlay');
        var box = modal.querySelector('.logout-modal-box');
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.45);display:flex;align-items:center;justify-content:center;padding:20px;';
        box.style.cssText = 'background:#fff;padding:28px 32px;border-radius:12px;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,0.2);min-width:280px;';
        modal.querySelector('.logout-modal-msg').style.cssText = 'margin:0 0 20px 0;font-size:1.1rem;color:#1a1a1a;font-family:system-ui,sans-serif;';
        var btns = modal.querySelector('.logout-modal-btns');
        btns.style.cssText = 'display:flex;gap:12px;justify-content:center;';
        [].forEach.call(modal.querySelectorAll('.logout-modal-btn'), function(btn) {
            btn.style.cssText = 'padding:10px 24px;border-radius:8px;font-size:1rem;font-weight:600;cursor:pointer;border:none;';
        });
        modal.querySelector('.logout-modal-no').style.cssText += 'background:#e5e7eb;color:#374151;';
        modal.querySelector('.logout-modal-yes').style.cssText += 'background:#0d5c3d;color:#fff;';

        modal.querySelector('.logout-modal-no').onclick = function() {
            modal.style.display = 'none';
        };
        modal.querySelector('.logout-modal-overlay').onclick = function(e) {
            if (e.target === overlay) modal.style.display = 'none';
        };
        return modal;
    }

    window.showLogoutConfirm = function(onYes, message) {
        var m = getModal();
        var msgEl = m.querySelector('.logout-modal-msg');
        if (msgEl) msgEl.textContent = message || 'You sure want to logout?';
        var yesBtn = m.querySelector('.logout-modal-yes');
        yesBtn.onclick = function() {
            m.style.display = 'none';
            if (typeof onYes === 'function') onYes();
        };
        m.style.display = 'flex';
    };
})();
