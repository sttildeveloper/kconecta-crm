(function () {
    'use strict';
    const name = 'kconecta_consent';
    const version = document.querySelector('meta[name="cookie-consent-version"]')?.content || '1';
    const banner = document.getElementById('cookieBanner');
    const listeners = { analytics: [], advertising: [] };

    function read() {
        const prefix = name + '=';
        const raw = document.cookie.split(';').map(value => value.trim()).find(value => value.startsWith(prefix));
        if (!raw) return null;
        try {
            const consent = JSON.parse(decodeURIComponent(raw.substring(prefix.length)));
            return consent.version === version ? consent : null;
        } catch (_) { return null; }
    }

    function runAllowed(consent) {
        ['analytics', 'advertising'].forEach(category => {
            if (consent?.categories?.[category]) listeners[category].splice(0).forEach(callback => callback());
        });
    }

    function save(categories) {
        const consent = { version, decided_at: new Date().toISOString(), categories: {
            necessary: true, analytics: Boolean(categories.analytics), advertising: Boolean(categories.advertising)
        }};
        document.cookie = name + '=' + encodeURIComponent(JSON.stringify(consent)) + '; Max-Age=31536000; Path=/; SameSite=Lax; Secure';
        banner?.classList.add('hide');
        window.dispatchEvent(new CustomEvent('kconecta:consent-changed', { detail: consent }));
        runAllowed(consent);
        return consent;
    }

    function onAllowed(category, callback) {
        const consent = read();
        if (consent?.categories?.[category]) callback();
        else if (listeners[category]) listeners[category].push(callback);
    }

    function openPreferences() { banner?.classList.remove('hide'); }
    document.querySelectorAll('[data-cookie-action="accept"]').forEach(button => button.addEventListener('click', () => save({ analytics: true, advertising: true })));
    document.querySelectorAll('[data-cookie-action="deny"]').forEach(button => button.addEventListener('click', () => save({ analytics: false, advertising: false })));
    document.querySelectorAll('[data-cookie-manage]').forEach(button => button.addEventListener('click', openPreferences));
    window.KConectaConsent = { read, save, acceptAll: () => save({ analytics: true, advertising: true }), denyOptional: () => save({ analytics: false, advertising: false }), openPreferences, onAllowed };
    window.cookieConfig = window.KConectaConsent.acceptAll;
    const current = read();
    if (!current) openPreferences(); else runAllowed(current);
})();
