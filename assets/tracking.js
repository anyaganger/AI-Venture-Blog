/**
 * Simple page view tracking for anya.ganger.com
 */
(function() {
    // Don't track admin pages
    if (window.location.pathname.includes('/admin')) {
        return;
    }

    // Generate or retrieve session ID
    let sessionId = sessionStorage.getItem('anya_session');
    if (!sessionId) {
        sessionId = 'sess_' + Math.random().toString(36).substr(2, 9) + Date.now().toString(36);
        sessionStorage.setItem('anya_session', sessionId);
    }

    // Track page view
    const trackPageView = function() {
        const data = {
            path: window.location.pathname,
            title: document.title,
            referrer: document.referrer || null,
            sessionId: sessionId
        };

        // Use sendBeacon for reliability, fallback to fetch
        const url = 'https://anya.ganger.com/api/analytics.php';
        const payload = JSON.stringify(data);

        if (navigator.sendBeacon) {
            navigator.sendBeacon(url, payload);
        } else {
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: payload,
                keepalive: true
            }).catch(function() {});
        }
    };

    // Track on page load
    if (document.readyState === 'complete') {
        trackPageView();
    } else {
        window.addEventListener('load', trackPageView);
    }
})();
